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
import sys
import pprint
import ConfigParser

from twisted.python import log

if not os.getenv('PYFARM_DISABLE_STD_LOGGING'):
    log.startLogging(sys.stdout)

cwd = os.path.dirname(os.path.abspath(__file__))
root = os.path.abspath(os.path.join(cwd, ".."))

FILES = []
FILENAMES = []

# ensure there are not any duplicate configuration file names
# and build a list of all configuration files
for root, dirs, files in os.walk(root):
    for filename in files:
        path = os.path.join(root, filename)
        if path.endswith(".ini"):
            name = os.path.basename(path)

            # raise an exception if we find two files with the same name
            if name in FILENAMES:
                raise RuntimeError("duplicate config name '%s'" % name)

            FILES.append(path)
            FILENAMES.append(name)

class Preferences(object):
    '''
    Loads multiple preference files onto a single object

    :exception IOError:
        raised if we can't find requested preferences
    '''
    def __init__(self, name='default'):
        self.cfg = ConfigParser.ConfigParser()
        self.name = name
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

    def __find(self, name):
        '''
        returns the full path to the configuration file matching
        name

        :exception OSError:
            raised if we fail to find a file matching name
        '''
        name = self.__name(name)

        for path in FILES:
            if path.endswith(name):
                return path

        raise OSError("no configuration file '%s'" % name)
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

    def read(self, name):
        '''
        reads a file into the config parser and
        logs the entry or raises an exception if the
        file cannot be found
        '''
        path = self.__find(name)
        self.cfg.read(path)
        log.msg("%s loaded config %s" % (self.name, path))
    # end read

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
        if key.isupper() and key not in ('FILES', 'FILENAMES'):
            local[key] = value

    pprint.pprint(local)
# end debug

def getUrl():
    '''returns the sql url based on preferences'''
    url = "%s" % DB_ENGINE

    # add the driver if it was provided in the preferences
    if DB_DRIVER:
        url += "+%s" % DB_DRIVER
        print "====",url

    # the start of the url changes slightly for sqlite connections
    url += "://"
    if DB_ENGINE == "sqlite":
        url += "/"

    # server and login related preferences do not
    # apply to sqlite
    if DB_ENGINE == "sqlite":
        return url + DB_NAME

    # add username, password, and host
    url += "%s:%s@%s" % (DB_USER, DB_PASS, DB_HOST)

    # add port if it was provided
    if DB_PORT and isinstance(DB_PORT, types.IntType):
        url += ":%i" % DB_PORT

    # finally, add the database name
    url += "/%s" % DB_NAME

    return url
# end getUrl

# local preferences setup
prefs = Preferences('common')
prefs.read('common')
prefs.read('database')

# local preferences
LOGGING_STDOUT = prefs.getboolean('LOGGING', 'stdout')
LOGGING_FILE = prefs.getboolean('LOGGING', 'file')
LOGGING_EXTENSION = prefs.get('LOGGING', 'extension')
LOGGING_ROLLOVER = prefs.getboolean('LOGGING', 'rollover')
LOGGING_ROLLOVER_SIZE = prefs.getint('LOGGING', 'rollover_size') * 1024
LOGGING_ROLLOVER_COUNT = prefs.getint('LOGGING', 'rollover_count')
LOGGING_KEYWORD_FORMAT = prefs.get('LOGGING', 'keyword_format')
LOGGING_DIVISION_LENGTH = prefs.getint('LOGGING', 'division_length')
LOGGING_DIVISION_SEP = prefs.get('LOGGING', 'division_sep')
LOGGING_TIMESTAMP = prefs.get('LOGGING', 'timestamp')
SHUTDOWN_ENABLED = prefs.getboolean('SHUTDOWN', 'enabled')
RESTART_ENABLED = prefs.getboolean('RESTART', 'enabled')
RESTART_DELAY = prefs.getint('RESTART', 'delay')
SERVER_PORT = prefs.getint('NETWORK', 'server_port')
CLIENT_PORT = prefs.getint('NETWORK', 'client_port')
MULTICAST_GROUP = prefs.get('MULTICAST', 'group')
MULTICAST_HEARTBEAT_PORT = prefs.getint('MULTICAST', 'heartbeat_port')
MULTICAST_HEARTBEAT_STRING = prefs.get('MULTICAST', 'heartbeat_string')

# database preferences
DB_CONFIG = prefs.get('DATABASE', 'config')
DB_HOST = prefs.get(DB_CONFIG, 'host', default='localhost')
DB_PORT = prefs.getint(DB_CONFIG, 'port')
DB_USER = prefs.get(DB_CONFIG, 'user')
DB_PASS = prefs.get(DB_CONFIG, 'pass')
DB_NAME = prefs.get(DB_CONFIG, 'name')
DB_ENGINE = prefs.get(DB_CONFIG, 'engine')
DB_DRIVER = prefs.get(DB_CONFIG, 'driver')
DB_URL = getUrl()
DB_REBUILD = prefs.getboolean('DATABASE', 'rebuild')
DB_ECHO = prefs.getboolean('DATABASE', 'echo')

if __name__ == '__main__':
    debug(locals())
