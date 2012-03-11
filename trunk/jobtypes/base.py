# No shebang line, this module is meant to be imported
#
# This file is part of PyFarm.
# Copyright (C) 2008-2012 Oliver Palmer
#
# PyFarm is free software: you can redistribute it and/or modify
# it under the terms of the GNU Lesser General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# PyFarm is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public License
# along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.

import os
import copy
import string
import ctypes
import socket
import getpass
import logging
import itertools

from common.preferences import prefs
from common import logger, datatypes
from common.db import Transaction, tables
from common.db.query import hosts

USERNAME = getpass.getuser()
HOSTNAME = socket.getfqdn()

class Job(logger.LoggingBaseClass):
    '''
    Base jobtype inherited by all other jobtypes

    :param string command:
        The full path or name of the command to run.  If a full path is not
        proived it will based resolved prior to being set.

    :param string or list args:
        arguments to provide to the command when being run

    :param integer jobid:
        the id of the job in the database

    :param string frame:
        the frame we will expect to be running

    :param string user:
        If a user is not provided then we assume we will run the job as the
        current user.  Providing a string will set self.user to the provided
        value but only if we have permission to run processes as other users.

    :param dict environ:
        custom environment variables to pass along
    '''
    def __init__(self, command, args, frame, user=None, environ=None):
        # base arguments which are used to set the non-private
        # class attributes
        self._jobid = None
        self._frameid = None
        self._command = command
        self._args = args
        self._user = user
        self._environ = environ

        self.frame = frame

        # first setup logging so we can capture output from the
        self.setupLog()

        # performs the setup to setup the class attributes
        self.log(
            "Setting up jobtype: %s" % self.__class__.__name__,
            level=logging.INFO
        )

        self.setupEnvironment()
        self.setupUser()
        self.setupCommand()
        self.setupArguments()
    # end __init__

    def dbsetup(self):
        '''
        Performs a database lookup to retrieve the frame object.  In addition
        this method also sets the state of the parent job, frame, and the
        assigned hostname for the frame.

        :exception RuntimeError:
            raise if we cannot find the job and/or the host
            in the database
        '''
        # ensure _frameid and _jobid have been set
        if self._frameid is None or self._jobid is None:
            raise AttributeError("_frameid or _jobid is None")

        # lookup the parent job object
        system = "jobtypes.base.lookup"
        with Transaction(tables.jobs, system=system) as trans:
            query = trans.query.filter(tables.jobs.c.id == self._jobid)
            job = query.first()

            # ensure job exists in the database
            if job is None:
                raise RuntimeError(
                    "unxpected NoneType for job %s from database" % self._jobid
                )

            # ensure the state of the parent is set to running
            state = job.state
            not_running = state != datatypes.State.RUNNING
            active = state in datatypes.ACTIVE_JOB_STATES
            if active and not_running:
                job.state = datatypes.State.RUNNING
                trans.log("set job %i state to running" % job.id)

            job = copy.deepcopy(job)

        # lookup the frame object
        with Transaction(tables.frames, system=system) as trans:
            query = trans.query.filter(tables.frames.c.id == self._frameid)
            frame = query.first()

            # ensure the frame exists in the database
            if frame is None:
                raise RuntimeError(
                    "frame id %i does not exist in the database" % self._frameid
                )

            # set the state and hostname of the frame
            frame.state = datatypes.State.RUNNING

            # retrieve the host id
            hostid = hosts.hostid(HOSTNAME)
            if hostid is None:
                raise RuntimeError("%s does not exist in %s" % (HOSTNAME, trans.table))

            frame.host = hostid
            trans.log("set state and hostname for frame")

            frame = copy.deepcopy(query.first())

        # add the job object to the frame object
        frame.job = job

        return frame
    # end dbsetup

    def assign(self):
        '''
        marks the frame as assigned in the database and ensures the
        state of the parent job is set to running
        '''
        system = "jobtypes.base.assign"

    # end assign

    def setupLog(self):
        '''Sets up the log file and begins logging the progress of the job'''
        self.log("...setting up log")
        root = prefs.get('logging.locations.jobs')
        template_vars = {
            "jobid" : self.frame.job.id,
            "frame" : self.frame.frame
        }
        template = string.Template(root)
        self.logfile = template.substitute(template_vars)
        self.observer = logger.Observer(self.logfile)
        self.observer.start()
    # end setupLog

    def setupEnvironment(self):
        '''
        Base setup environment setup method.  Normally this will just use
        values provided by self._environ along with the os environment.
        '''
        self.environ = {}
        if isinstance(self._environ, dict) and self._environ:
            self.log("...setting up custom base environment")
            self.environ = copy.deepcopy(self._environ)

        # add the native os environment if it does not match our
        # current env
        if os.environ != self.environ:
            self.log("...updating environment with os environment")
            data = dict(os.environ)
            self.environ.update(data)
    # end setupEnvironment

    def setupCommand(self):
        '''
        Ensures the command we are attempting to run exists and is accessible

        :exception OSError:
            Raised if the command does not exist or of we do not have permission
            to run the requested command.  Because this is being handled in the
            event loop this error will need to be handled externally.
        '''
        self.command = None
        self.log("...setting up command")

        # not much to do if the path we were provided already exists
        if os.path.isfile(self._command):
            self.command = os.path.abspath(self._command)
            self.log("...command set to %s" % self.command)
            return

        else:
            # combine any additional paths from the environment
            # we passed in with the paths from preferences
            paths = prefs.get('jobtypes.path')
            for entry in self.environ.get('PATH').split(os.pathsep):
                if entry not in paths:
                    self.log(".....inserting %s from the environment" % entry)
                    paths.insert(0, entry)

            command_names = set()
            command_names.add(self._command)

        if datatypes.OS == datatypes.OperatingSystem.WINDOWS:
            # construct a list of all possible commands
            command_names.add(self._command.lower())
            command_names.add(self._command.upper())

            # iterate over all possible command names and extensions
            # and construct a list of commands
            commands = set()
            extensions = prefs.get('jobtypes.extensions')
            extensions.append("")

            for command, extension in itertools.product(command_names, extensions):
                if extension:
                    commands.add(os.extsep.join((command, extension)))
                else:
                    commands.add(command)

            for path, command in itertools.product(paths, commands):
                path = os.path.join(path, command)
                if os.path.isfile(path):
                    self.command = path

        else:
            for path, command in itertools.product(paths, command_names):
                path = os.path.join(path, command)
                if os.path.isfile(path):
                    self.command = path

        # ensure the command was setup properly created
        if self.command is None or not os.path.isfile(self.command):
            raise OSError("failed to find the '%s' command" % self._command)

        self.log("...command set to %s" % self.command)
    # end setupCommand

    def setupArguments(self):
        '''Sets of arguments to use for the command'''
        if isinstance(self._args, str):
            self.args = self._args.split()
        else:
            self.args = self._args[:]

        if not self.args:
            self.log("...no arguments constructed", level=logging.WARNING)
        else:
            self.log("...arguments: %s" % self.args)
    # end setupArguments

    def setupUser(self):
        '''
        If no user is provided then we a assume we should run as the current
        user.  Should a user be proved that is not the current user however
        this method will check to be we can change the process owner.

        :exception OSError:
            Raised if we do not have permission to change users.  Please note
            although the jobtype will raise this error code that is
            initializing this class will need to handle the error for the
            reactor itself.
        '''
        # setup base attributes (overridden below)
        self.uid = None
        self.gid = None
        self.user = USERNAME

        if self.user is None:
            self.user = USERNAME

        if isinstance(self._user, str):
            # if the requested user is not the current user we need
            # to see if we are running as root/admin
            self.log("...checking for admin privileges")
            if self._user != USERNAME:
                # setting the process owner is only supported on
                # unix based systems
                if datatypes.OS in (
                    datatypes.OperatingSystem.LINUX,
                    datatypes.OperatingSystem.MAC,
                ):
                    if os.getuid():
                        raise OSError("you must be root to setuid")

                    # if we are running at root set the user name
                    # and retrieve the user id and group ids

                    try:
                        import pwd
                        ids = pwd.getpwnam(self._user)
                        self.uid = ids.pw_uid
                        self.gid = ids.pw_gid
                        self.user = self._user

                    except KeyError:
                        self.log(
                            "...no such user '%s' on system" % self._user,
                            level=logging.ERROR
                        )

                else:
                    # if we are running in windows, we should at least
                    # produce warnings if we are not an administrator
                    if datatypes.OS == datatypes.OperatingSystem.WINDOWS and \
                        ctypes.windll.shell32.IsUserAnAdmin():
                        msg = "not running as an administrator, this may produce "
                        msg += "unexpected results in some cases"
                        self.log(msg, level=logging.WARNING)

                    self.user = USERNAME

        self.log("...job will run as %s" % self.user)
    # end setupUser

    def preJob(self):
        '''Runs before the start of the job'''
        self.log("nothing to be done for preJob")
    # end preJob

    def preFrame(self):
        '''Runs before the start of a frame'''
        self.log("nothing to be done for preFrame")
    # end preFrame

    def postJob(self):
        '''Runs after a job completes, regardless of success'''
        self.log("nothing to be done for postJob")
    # end postJob

    def postJob_success(self):
        '''Runs after a job completes successfully'''
        self.log("nothing to be done for postJob_success")
    # end postJob_success

    def postJob_failure(self):
        '''Runs after a job fails'''
        self.log("nothing to be done for postJob_failure")
    # end postJob_failure

    def postFrame(self):
        '''Runs after a frame completes, regardless of success'''
        self.log("nothing to be done for postFrame")
    # end postFrame

    def postFrame_success(self):
        '''Runs after a frame completes successfully'''
        self.log("nothing to be done for postFrame_success")
    # end postFrame_success

    def postFrame_failure(self):
        '''Runs after a frame fails'''
        self.log("nothing to be done for postFrame_failure")
    # end postFrame_failure

    def run(self):
        pass
    # end run
# end Job

if __name__ == '__main__':
    base = Job('ping', '-c 1 localhost', 123, 1004)
    base.run()
