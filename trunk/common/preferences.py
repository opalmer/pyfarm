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

from __future__ import with_statement

import os
import yaml
import string
import tempfile

import datatypes

from twisted.python import log

cwd = os.path.dirname(os.path.abspath(__file__))
root = os.path.abspath(os.path.join(cwd, ".."))
if not os.path.isdir(ETC):
    raise OSError("configuration directory does not exist: %s" % ETC)

class Preferences(object):
    '''
    Preferences object which handles loading of configuration
    files and handling of specific special case (such as logging
    directory strings)
    '''
    # determine the yaml loader we should use
    if hasattr(yaml, 'CLoader'):
        LOADER = yaml.CLoader
    else:
        LOADER = yaml.SafeLoader

    def __init__(self):
        self.loaded = []
        self.data = {}
    # end __init__

    def __load(self, name, filename):
        '''
        Loads the requested filename into self.data[name] if the given
        filename has not already been loaded
        '''
        # ensure the config exists
        path = os.path.join(ETC, filename)
        if not os.path.isfile(path):
            raise OSError("configuration does not exist: %s" % path)

        with open(path, 'r') as stream:
            data = yaml.load(stream, Loader=Preferences.LOADER)
            self.data[name] = data
            self.loaded.append(filename)
            print "Loaded: %s" % path
    # end __load

    def __pathjoin(self, value):
        '''
        splits the given value by / then combines the results
        using os.path.join
        '''
        paths = value.split("/")
        return os.sep.join(paths)
    # end __pathjoin

    def __loggingLocations(self, data, kwargs):
        template = string.Template(data)
        template_vars = {
            "root" : self.get('logging.roots.%s' % datatypes.OSNAME, **kwargs),
            "temp" : tempfile.gettempdir()
        }

        # remove any keys that are already in kwargs so
        # we can override the template vars
        for key in template_vars:
            if key in kwargs:
                del template_vars[key]

        # update template_vars from the provided kwargs
        template_vars.update(kwargs)

        # after substituting the template split the path
        # and recombine it using os.path.join
        data = template.safe_substitute(template_vars)
        return self.__pathjoin(data)
    # end __loggingLocations

    # TODO: verify behavior (esp. on windows)
    def __expandSearchPaths(self, data, kwargs):
        '''expands the path(s) provided by data and returns the results'''
        paths = []

        # expand all environment variables and then split on ':' to retrieve
        # the paths from the string
        for value in [os.path.expandvars(value) for value in data]:
            if ":" in value:
                for entry in value.split(":"):
                    paths.append(entry)
            else:
                paths.append(value)

        # finally iterate over each paths and see if anything is
        # pointing to the farm root
        template_vars = {
            "root" : self.get('logging.roots.%s' % datatypes.OSNAME, **kwargs)
        }

        # remove any keys that are already in kwargs so
        # we can override the template vars
        for key in template_vars:
            if key in kwargs:
                del template_vars[key]

        results =  []
        for path in paths:
            template = string.Template(path)

            # only add paths which are not already part of results
            path = template.safe_substitute(template_vars)
            if path not in results:
                results.append(path)

        return results
    # end __expandSearchPaths

    def get(self, key, **kwargs):
        '''
        Retrieve the preferences when provided a key.  For example
        to retrieve the port setting for the client:

            >>> prefs = Preferences()
            >>> prefs.get('network.ports.client')
            9031

        :param string key:
            The entry to retrieve from a file in etc.  Format is:
                filename.key.key....

        :param boolean reload:
            Forces the data from the preference files to be reload.  This
            option should be used sparingly because it will also cause
            recursive lookups to reload their files as well.

        :param dictionary kwargs:
            extra arguments to pass to template strings

        :exception ValueError:
            raised if key does not contain the expected '.' separator or
            if we did not find at least one key after the file name.

        :exception KeyError:
            raised if we failed to fully resolve the preference
        '''
        if "." not in key:
            raise ValueError("key provided does not contain the '.' separator")

        values = [ value for value in key.split('.') if value ]

        if len(values) < 2:
            raise ValueError("need at least one key after the filename")

        name = values[0]
        filename = "%s.yml" % name

        # load the yaml file if it as not been already
        if kwargs.get('reload') or filename not in self.loaded:
            self.__load(name, filename)

        # traverse the values and retrieve the data
        try:
            value = name
            data = self.data[name]
            for value in values[1:]:
                data = data[value]

        except KeyError:
            error =  "could not find '%s' data when searching %s" % (value, key)
            raise KeyError(error)


        if key.startswith("logging.locations."):
            data = self.__loggingLocations(data, kwargs)

        elif key == "jobtypes.path":
            data = self.__expandSearchPaths(data, kwargs)

        return data
    # end get
# end Preferences

ETC = os.path.join(root, 'etc')
prefs = Preferences()

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
    if DB_PORT and isinstance(DB_PORT, int):
        url += ":%i" % DB_PORT

    # finally, add the database name
    url += "/%s" % DB_NAME

    return url
# end getUrl
#
## local preferences setup
#prefs = Preferences('common')
#prefs.read('common')
#prefs.read('database')
#
#LOGROOT = prefs.get('LOGROOTS', datatypes.OSNAME)
#
#def logdir_jobs():
#    '''returns the job log directory'''
#    data = prefs.get('LOG_DIRECTORIES', 'jobs')
#    paths = data.split("/")
#    print path
## local preferences
#
#LOGDIR_GENERAL = prefs.get('LOG_DIRECTORIES', 'general')
##LOGDIR_JOB =
#LOGGING_ROLLOVER_COUNT = prefs.getint('LOGGING', 'rollover_count')
#LOGGING_TIMESTAMP = prefs.get('LOGGING', 'timestamp')
#SHUTDOWN_ENABLED = prefs.getboolean('SHUTDOWN', 'enabled')
#RESTART_ENABLED = prefs.getboolean('RESTART', 'enabled')
#RESTART_DELAY = prefs.getint('RESTART', 'delay')
#SERVER_PORT = prefs.getint('NETWORK', 'server_port')
#CLIENT_PORT = prefs.getint('NETWORK', 'client_port')
#MULTICAST_GROUP = prefs.get('MULTICAST', 'group')
#MULTICAST_HEARTBEAT_PORT = prefs.getint('MULTICAST', 'heartbeat_port')
#MULTICAST_HEARTBEAT_STRING = prefs.get('MULTICAST', 'heartbeat_string')
#
## database preferences
#DB_CONFIG = prefs.get('DATABASE', 'config')
#DB_HOST = prefs.get(DB_CONFIG, 'host', default='localhost')
#DB_PORT = prefs.getint(DB_CONFIG, 'port')
#DB_USER = prefs.get(DB_CONFIG, 'user')
#DB_PASS = prefs.get(DB_CONFIG, 'pass')
#DB_NAME = prefs.get(DB_CONFIG, 'name')
#DB_ENGINE = prefs.get(DB_CONFIG, 'engine')
#DB_DRIVER = prefs.get(DB_CONFIG, 'driver')
#DB_URL = getUrl()
#DB_REBUILD = prefs.getboolean('DATABASE', 'rebuild')
#DB_ECHO = prefs.getboolean('DATABASE', 'echo')
#DB_CLOSE_CONNECTIONS = prefs.getboolean('DB-CONNECTIONS', 'close_transactions')
#
#if __name__ == '__main__':
#    debug(locals())
