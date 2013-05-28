# No shebang line, this module is meant to be imported
#
# Copyright 2013 Oliver Palmer
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#    http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.

from __future__ import with_statement

import os
import string
import ctypes
import itertools
from sqlalchemy import orm

from pyfarm import errors
from pyfarm.config.core import Loader
from pyfarm.datatypes.enums import OperatingSystem
from pyfarm.logger import Logger, Observer
from pyfarm.datatypes.system import USER, OS
from pyfarm.db import session

fsprefs = Loader("filesystem.yml")
jtprefs = Loader("jobtypes.yml")


class BaseJob(Logger):
    """
    Base jobtype inherited by all other jobtypes

    :param dict substitute_data:
        data which can be used to into each argument
    """
    def __init__(self, row_job, row_frame, substitute_data=None):
        Logger.__init__(self, self)
        self.__row_job = row_job
        self.__row_frame = row_frame

        # base arguments which are used to set the non-private
        # class attributes
        self.__jobid = self.__row_job.id
        self.__frameid = self.__row_frame.id
        self.__command = self.__row_job.cmd
        self.__args = self.__row_job.args
        self.__user = self.__row_job.user
        self.__environ = self.__row_job.environ
        self.frame = int(self.__row_frame.frame)
        self.logfile = None
        self.process = None

        self.substitute_data = substitute_data or {
            'frame' : self.frame,
            'jobid' : self.__jobid,
            'frameid' : self.__frameid,
            'user' : self.__user,
            'cpus' : self.__row_job.cpus,
            'root' : prefs.get('filesystem.root')
        }

        # first setup logging so we can capture output moving
        # forward
        self.setupLog()

        # performs the setup to setup the class attributes
        self.info("Setting up %s" % self.__class__.__name__)

        # prep environment and user name
        self.setupEnvironment()
        self.setupUser()
        self.setupCommand()
        self.setupArguments()
    # end __init__

    def validateRequirements(self):
        """
        Validates information in the job's data column if DATA_REQUIREMENTS
        exists on the class.

        :exception TypeError:
            raised if self.DATA_REQUIREMENTS is not a dictionary

        :exception AttributeError:
            raised if self.frame has not been setup

        :exception KeyError:
            raised if self.frame.job.data does not contain one
            or more of the keys we are expecting to find when validating
            data
        """
        # do nothing but print a warning if we are missing
        # DATA_REQUIREMENTS
        if not hasattr(self.__class__, 'DATA_REQUIREMENTS'):
            msg = "cannot validate requirements, class does not contain "
            msg += "the required DATA_REQUIREMENTS"
            self.warning(msg)
            return

        # raise a TypeError if DATA_REQUIREMENTS exists but
        # is of the wrong type
        if not isinstance(self.DATA_REQUIREMENTS, dict):
            raise TypeError("DATA_REQUIREMENTS ")

        # do nothing if DATA_REQUIREMENTS is empty
        if not self.DATA_REQUIREMENTS:
            self.debug("DATA_REQUIREMENTS is empty, skipping validateRequirements")
            return

        # ensure the frame object has been setup
        if not hasattr(self, 'frame') or not getattr(self, 'frame'):
            raise AttributeError

        for key, expected_types in self.DATA_REQUIREMENTS.items():
            # ensure data actually has the key we are
            # expecting
            if key not in self.job.data:
                raise KeyError("job data does not contain the %s key" % key)

            value = self.job.data[key]
            if not isinstance(value, expected_types):
                msg = "unexpected type for %s (%s), expected " % (key, type(value))
                msg += "%s" % expected_types
                raise TypeError(msg)
        # end validateRequirements

    def setupLog(self):
        """Sets up the log file and begins logging the progress of the job"""
        if self.logfile is None:
            self.debug("...setting up log")
            root = fsprefs.get('locations.jobs')
            template = string.Template(root)
            self.logfile = template.substitute(self.substitute_data)
            self.observer = Observer(self.logfile)
            self.addObserver(self.observer)
        else:
            self.debug("logfile already setup: %s" % self.logfile)
    # end setupLog

    def setupEnvironment(self):
        """
        Base setup environment setup method.  Normally this will just use
        values provided by self._environ along with the os environment.
        """
        self.environ = {}
        if isinstance(self.__environ, dict) and self.__environ:
            self.debug("...setting up custom base environment")
            self.environ = self.__environ.copy()

        # add the native os environment if it does not match our
        # current env
        if os.environ != self.environ:
            self.debug("...updating environment with os environment")
            data = dict(os.environ)
            self.environ.update(data)
    # end setupEnvironment

    def setupCommand(self):
        """
        Ensures the command we are attempting to run exists and is accessible

        :exception OSError:
            Raised if the command does not exist or of we do not have permission
            to run the requested command.  Because this is being handled in the
            event loop this error will need to be handled externally.
        """
        self.command = None
        self.debug("...setting up command")

        # not much to do if the path we were provided already exists
        if os.path.isfile(self.__command):
            self.command = str(os.path.realpath(self.__command))
            self.debug("...command set to %s" % self.command)
            return

        else:
            # combine any additional paths from the environment
            # we passed in with the paths from preferences
            paths = jtprefs.get('path')
            for entry in self.environ.get('PATH').split(os.pathsep):
                if entry not in paths:
                    self.debug(".....inserting %s from the environment" % entry)
                    paths.insert(0, entry)

            command_names = set()
            command_names.add(self.__command)

        if OS == OperatingSystem.WINDOWS:
            # construct a list of all possible commands
            command_names.add(self.__command.lower())
            command_names.add(self.__command.upper())

            # iterate over all possible command names and extensions
            # and construct a list of commands
            commands = set()
            extensions = jtprefs.get('extensions')
            extensions.append("")

            for command, extension in itertools.product(command_names, extensions):
                if extension:
                    commands.add(os.extsep.join((command, extension)))
                else:
                    commands.add(command)

            for path, command in itertools.product(paths, commands):
                path = os.path.join(path, command)
                if os.path.isfile(path):
                    self.command = str(os.path.realpath(path))

        else:
            for path, command in itertools.product(paths, command_names):
                path = os.path.join(path, command)
                if os.path.isfile(path):
                    self.command = str(os.path.realpath(path))

        # ensure the command was setup properly created
        if self.command is None or not os.path.isfile(self.command):
            raise OSError("failed to find the '%s' command" % self.__command)

        self.debug("...command set to %s" % self.command)
    # end setupCommand

    def setupArguments(self):
        """Sets of arguments to use for the command"""
        args = []

        for arg in self.__args:
            template = string.Template(arg)
            value = template.safe_substitute(self.substitute_data)
            args.append(value)

        if self.command != args[0]:
            self.debug("...inserting command as first argument")
            args.insert(0, str(self.command))

        self.args = args

        if not self.args:
            self.warning("...no arguments constructed")
        else:
            self.debug("...arguments: %s" % self.args)
    # end setupArguments

    def setupUser(self):
        """
        If no user is provided then we a assume we should run as the current
        user.  Should a user be proved that is not the current user however
        this method will check to be we can change the process owner.

        :exception OSError:
            Raised if we do not have permission to change users.  Please note
            although the jobtype will raise this error code that is
            initializing this class will need to handle the error for the
            reactor itself.
        """
        # setup base attributes (overridden below)
        self.uid = None
        self.gid = None
        self.user = USER

        if self.user is None:
            self.user = USER

        if isinstance(self.__user, (str, unicode)):
            # if the requested user is not the current user we need
            # to see if we are running as root/admin
            self.debug("...checking for admin privileges")
            if self.__user != USER:
                # setting the process owner is only supported on
                # unix based systems
                if OS in (OperatingSystem.LINUX, OperatingSystem.MAC):
                    if os.getuid():
                        raise OSError("you must be root to setuid")

                    # if we are running at root set the user name
                    # and retrieve the user id and group ids
                    try:
                        import pwd
                        ids = pwd.getpwnam(self.__user)
                        self.uid = ids.pw_uid
                        self.gid = ids.pw_gid
                        self.user = self.__user

                    except KeyError:
                        self.error("...no such user '%s' on system" % self.__user)

                else:
                    # if we are running in windows, we should at least
                    # produce warnings if we are not an administrator
                    if OS == OperatingSystem.WINDOWS and \
                        ctypes.windll.shell32.IsUserAnAdmin():
                        msg = "not running as an administrator, this may produce "
                        msg += "unexpected results in some cases"
                        self.warning(msg)

                    self.user = USER

        self.debug("...job will run as %s" % self.user)
    # end setup_user

    def preJob(self):
        """Runs before the start of the job"""
        self.debug("nothing to be done for preJob")
    # end preJob

    def preFrame(self):
        """Runs before the start of a frame"""
        self.debug("nothing to be done for preFrame")
    # end preFrame

    def postJob(self):
        """Runs after a job completes, regardless of success"""
        self.debug("nothing to be done for postJob")
    # end postJob

    def postFrame(self):
        """Runs after a frame completes, regardless of success"""
        self.debug("nothing to be done for postFrame")
    # end postFrame

    def run(self):
        """runs the job itself"""
        cmd = "%s %s" % (self.command, " ".join(self.args))
        self.process = None
    # end run
# end BaseJob


class Frame(Logger, BaseJob):
    def __init__(self, id):
        self.id = id
        self.row_frame = None
        self.row_job = None

        Logger.__init__(self, self)
        self.retrieveRows()
        BaseJob.__init__(self, self.row_job, self.row_frame)
    # end __init__

    def retrieveRows(self):
        """retrieves the frame and job rows from the database"""
        # don't do anything if we have already fetched
        # the required rows
        if self.row_frame is not None and self.row_frame is not None:
            return

        self.debug("retrieving database entry for frame id %s" % self.id)
        scoped_session = orm.scoped_session(session.Session)

        with contexts.Connection(scoped_session.connection()):
            self.debug("...querying frame id %s" % self.id)
            frames_query = scoped_session.query(frames)
            self.row_frame = frames_query.filter(frames.c.id == self.id).first()

            # raise an exception if we fail to find the frame
            # we're looking for
            if self.row_frame is None:
                raise errors.FrameNotFound(id=self.id)

            self.debug("...querying jobid id %s" % self.row_frame.jobid)
            jobs_query = scoped_session.query(jobs)
            self.row_job = jobs_query.filter(jobs.c.id == self.row_frame.jobid).first()


            # raise an exception if we fail to find the job
            # we're looking for
            if self.row_job is None:
                raise errors.JobNotFound(id=self.__jobid)
    # end retrieveRows
# end Frame
