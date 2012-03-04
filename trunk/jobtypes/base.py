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
import types
import getpass
import logging

from common import logger
from common import datatypes

from twisted.python import log

USERNAME = getpass.getuser()

class Base(object):
    '''
    Base jobtype inherited by all other jobtypes

    :param string command:
        The full path or name of the command to run.  If a full path is not
        proived it will based resolved prior to being set.

    :param string or list args:
        arguments to provide to the command when being run

    :param string user:
        If a user is not provided then we assume we will run the job as the
        current user.  Providing a string will set self.user to the provided
        value but only if we have permission to run processes as other users.

    :param dict environ:
        custom environment variables to pass along
    '''
    def __init__(self, command, args=[], user=None, environ={}):
        # base argumnets which are used to set the non-private
        # class attributes
        self._command = command
        self._args = args
        self._user = user
        self._environ = environ

        # performs the setup to setup the class attributes
        log.msg("Setting up jobtype: %s" % self.__class__.__name__)
        self.setupEnvironment()
        self.setupUser()
        self.setupCommand()
        self.setupArguments()
    # end __init__

    def setupEnvironment(self):
        '''
        Base setup environment setup method.  Normally this will just use
        values provided by self._environ along with the os environment.
        '''
        self.environ = {}
        if isinstance(self._environ, types.DictionaryType) and self._environ:
            log.msg("...setting up custom base environment")
            self.environ = copy.deepcopy(self._environ)

        # add the native os environment if it does not match our
        # current env
        if os.environ != self.environ:
            log.msg("...updating custom env with os environment")
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
        # seealso: client.process.which
        # construct the full path to the command
        # ensure the command exists
        # ensure we have access to the command
        # ensure we can execute the command
        log.msg("...command NOT_SET")
    # end setupCommand

    def setupArguments(self):
        '''Sets of arguments to use for the command'''
        if isinstance(self._args, types.StringTypes):
            self.args = self._args.split()
        else:
            self.args = self._args[:]

        if not self.args:
            log.msg("...no arguments constructed", level=logging.WARNING)
        else:
            log.msg("...arguments: %s" % self.args)
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

        if isinstance(self._user, types.NoneType):
            self.user = USERNAME

        if isinstance(self._user, types.StringTypes):
            # if the requested user is not the current user we need
            # to see if we are running as root/admin
            log.msg("...checking for admin privileges")
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
                        log.msg(
                            "...no such user '%s' on system" % self._user,
                            level=logging.ERROR
                        )

                else:
                    self.user = USERNAME

        log.msg("...job will run as %s" % self.user)
    # end setupUser
# end Base

if __name__ == '__main__':
    base = Base()
