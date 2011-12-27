# No shebang line, this module is meant to be imported
#
# This file is part of PyFarm.
# Copyright (C) 2008-2011 Oliver Palmer
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
import pprint
import ConfigParser

from twisted.python import log

class Preferences(object):
    '''
    Loads multiple preference files onto a single object

    :exception IOError:
        raised if we have trouble finding the configuration
        directories or files
    '''
    def __init__(self, root, package):
        self.__root = root
        self.__package = package

        # main configuration directories
        self.CFG_ROOT = os.path.join(self.__root, "etc")
        self.CFG_PACKAGE = os.path.join(self.__package, "etc")

        # ensure the main configuration directory exists
        if not os.path.isdir(self.CFG_ROOT):
            raise IOError("root etc directory %s" % self.CFG_ROOT)

        # ensure the package configuration directory exists
        if not os.path.isdir(self.CFG_PACKAGE):
            error = "package etc directory does not exist %s" % self.CFG_PACKAGE
            raise IOError(error)

        # setup global config parser
        self.cfg = ConfigParser.ConfigParser()
    # end __init__

    def __name(self, name):
        '''
        standard method for converting a name into
        a valid file name
        '''
        ext = "ini"
        if not name.endswith(ext):
            name = "%s.%s" % (name, ext)

        return name
    # end __name

    def __find(self, root, name):
        '''
        returns the path to the file or raises an
        OSError if it cannot be found
        '''
        name = self.__name(name)
        path = os.path.join(root, name)

        if not os.path.isfile(path):
            raise OSError("no such configuration %s" % path)

        return path
    # end __find

    def __call(self, call, section, option, default):
        '''
        returns the value of call for the given section and option
        or returns default in the event of an error
        '''
        try:
            return call(section, option)
        except:
            return default
    # end __call

    def __getlist(self, section, option, sep):
        values = []
        data = self.__call(self.cfg.get, section, option, [])

        if data:
            for value in data.split(sep):
                if value in values:
                    continue

                values.append(value)

        return values
    # end __getlist

    def read(self, path):
        '''
        reads a file into the config parser and
        logs the entry
        '''
        self.cfg.read(path)
        log.msg("loaded config %s" % path)
    # end read

    def addRoot(self, name):
        path = self.__find(self.CFG_ROOT, name)
        self.read(path)
    # end addRoot

    def addPackage(self, name):
        path = self.__find(self.CFG_PACKAGE, name)
        self.read(path)
    # end addPackage

    def get(self, section, option, default=None):
        return self.__call(self.cfg.get, section, option, default)
    # end get

    def getboolean(self, section, option, default=None):
        return self.__call(self.cfg.getboolean, section, option, default)
    # end getboolean

    def getfloat(self, section, option, default=None):
        return self.__call(self.cfg.getfloat, section, option, default)
    # end getfloat

    def getint(self, section, option, default=None):
        return self.__call(self.cfg.getint, section, option, default)
    # end getint

    def getlist(self, section, option, sep=','):
        data = []
        for value in self.__getlist(section, option, sep):
            if value and value not in data:
                data.append(value)

        return data
    # end getlist

    def getenvlist(self, section, option, sep=','):
        envvars = []
        for value in self.__getlist(section, option, sep):
            if value not in envvars and value in os.environ:
                envvars.append(value)

        return envvars
    # end getenvlist
# end Preferences

def debug(localVars):
    '''pretty prints the current preferences'''
    local = {}
    for key, value in localVars.items():
        if key.isupper():
            local[key] = value

    pprint.pprint(local)
# end debug

# local preferences setup
cwd = os.path.abspath(os.path.dirname(__file__))
root = os.path.abspath(os.path.join(cwd, ".."))
package = os.path.abspath(os.path.join(cwd, ".."))
prefs = Preferences(root, package)
prefs.addRoot('common')

# local preferences
LOGGING_STDOUT = prefs.getboolean('LOGGING', 'stdout')
LOGGING_FILE = prefs.getboolean('LOGGING', 'file')
LOGGING_EXTENSION = prefs.get('LOGGING', 'extension')
LOGGING_ROLLOVER = prefs.getboolean('LOGGING', 'rollover')
LOGGING_ROLLOVER_SIZE = prefs.getint('LOGGING', 'rollover_size') * 1024
LOGGING_ROLLOVER_COUNT = prefs.getint('LOGGING', 'rollover_count')


if __name__ == '__main__':
    debug(locals())