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

import os
import string
import tempfile
import warnings

import psutil

from pyfarm import PYFARM_ETC, PYFARM_ROOT, fileio, errors
from pyfarm.datatypes.enums import OperatingSystem
from pyfarm.logger import Logger
from pyfarm.datatypes.system import OS, OSNAME


if not os.path.isdir(PYFARM_ETC):
    raise OSError("configuration directory does not exist: %s" % PYFARM_ETC)

class Preferences(Logger):
    """
    Preferences object which handles loading of configuration
    files and handling of specific special case (such as logging
    directory strings)
    """
    # creates a 'frozen' form of the preferences to override
    # the yaml files on disk.
    PREFERENCES = {}

    def __init__(self):
        Logger.__init__(self, self)
        self.loaded = []
        self.data = {}
    # end __init__

    def __load(self, name, filename):
        """
        Loads the requested filename into self.data[name] if the given
        filename has not already been loaded
        """
        # ensure the config exists
        path = os.path.join(PYFARM_ETC, filename)
        if not os.path.isfile(path):
            raise OSError("configuration does not exist: %s" % path)

        data = fileio.yml.load(path)
        self.data[name] = data

        if path not in self.loaded:
            self.loaded.append(path)
    # end __load

    def __pathjoin(self, value):
        """
        splits the given value by / then combines the results
        using os.path.join
        """
        paths = value.split("/")
        return os.sep.join(paths)
    # end __pathjoin

    def __loggingLocations(self, data, kwargs):
        template = string.Template(data)
        template_vars = {
            "root" : self.get('filesystem.roots.%s' % OSNAME, **kwargs),
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
        """expands the path(s) provided by data and returns the results"""
        paths = []

        # expand all environment variables and then split on ':' to retrieve
        # the paths from the string
        for value in [os.path.expandvars(value) for value in data]:
            if os.pathsep in value:
                for entry in value.split(os.pathsep):
                    paths.append(entry)
            else:
                paths.append(value)

        # finally iterate over each paths and see if anything is
        # pointing to the farm root
        template_vars = {
            "root" : self.get('filesystem.roots.%s' % OSNAME, **kwargs)
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
            if not os.path.isdir(path):
                self.debug("skipping %s, not a directory" % path)
                continue

            if path not in results:
                results.append(path)

        return results
    # end __expandSearchPaths

    def __dburl(self):
        """returns the url use for connecting to the database"""
        urls = []
        for db in self.get('database.setup.config'):
            config = self.get('database.%s' % db)

            # retrieve the settings from the config
            driver = config.get('driver')
            engine = config.get('engine')
            dbname = config.get('name')
            dbuser = config.get('user')
            dbpass = config.get('pass')
            dbhost = config.get('host')
            dbport = config.get('dbport')
            url = engine

            # adds the driver if it was found in the preferences
            if driver:
                # One last check before we do anything else.  If we happen to be
                # using the ctypes version of the psycopg2 driver then we need
                # it in sys.modules (this is here mainly for some pypy support)
                if driver == "psycopg2ct":
                    try:
                        from psycopg2ct import compat
                        compat.register()
                        self.debug("registered psycopg2ct")

                    except ImportError:
                        self.warning("failed to import psycopg2ct, skipping")
                        continue

                    driver = "psycopg2"

                url += "+%s" % driver

            url += "://"
            if engine == "sqlite":
                warnings.warn(
                    "sqlite is only supported for testing purposes",
                    RuntimeWarning
                )
                return [ "%s/%s" % (url, dbhost) ]

            # setup the username, password, host, and port
            url += "%s:%s@%s" % (dbuser, dbpass, dbhost)
            if isinstance(dbport, int):
                url += ":%i" % dbport

            url += "/%s" % dbname
            urls.append(url)

        if not urls:
            raise errors.ConfigurationError(
                msg="failed to retrieve any database urls based on the current config"
            )

        return urls
    # end __dburl

    def __calculateMaxJobs(self, data, kwargs):
        """calculates and returns the number of max jobs"""
        if data is None:
            return psutil.NUM_CPUS

        elif isinstance(data, int) and data:
            return data

        elif data == "relative" or not data:
            return psutil.NUM_CPUS / self.get('host.relative-value', **kwargs)
    # end  __calculateMaxJobs

    def __extensions(self, kwargs):
        """returns extensions for the current os"""
        results = set()

        # retrieve
        extensions = self.get('jobtypes.extensions.common', **kwargs )

        try:
            extensions.extend(
                self.get('jobtypes.extensions.%s' % OSNAME, **kwargs)
            )

        except KeyError:
            self.debug('no os entry for %s in extensions' % OSNAME)

        for extension in extensions:
            results.add(extension)
            results.add(extension.lower())

            if OS == OperatingSystem.WINDOWS:
                results.add(extension.upper())

        return [ result for result in results if result ]
    # end __extensions

    def __jobtypeSearchPaths(self, data, kwargs):
        paths = []
        for entry in data:
            path = os.path.sep.join(entry.split("/"))
            template = string.Template(entry)
            expanded_template = template.safe_substitute(
                    {
                        "pyfarm" : PYFARM_ROOT,
                        "root" : self.get('filesystem.roots.%s' % OSNAME, **kwargs)
                    }
            )
            paths.append(os.path.expandvars(expanded_template))
        return paths
    # end __jobtypeSearchPaths

    def set(self, key, value, overwrite=True):
        """
        Sets a preference to the provided value that will override the
        yaml file.  Normally this method should not need to be used but
        can be used by external libraries, unittests, and other processes
        that require a specific preference.

        :param string key:
            the key to create a frozen preference for

        :param value:
            the value to set the preference to, setting this value to None
            will remove the preference

        :param boolean overwrite:
            if false then we will not overwrite preferences that already exist
        """
        if value is None and key in Preferences.PREFERENCES:
            del Preferences.PREFERENCES[key]
            self.debug("deleted frozen preference %s" % key)

        else:
            # skip if the key already exists and overwrite is
            # turned off
            if key in Preferences.PREFERENCES and not overwrite:
                return

            Preferences.PREFERENCES[key] = value
            self.warning("freezing preference %s to %s" % (key, value))
    # end set

    def get(self, key, **kwargs):
        """
        Retrieve the preferences when provided a key.  For example
        to retrieve the port setting for the host:

            >>> prefs = Preferences()
            >>> prefs.get('network.ports.host')
            9031

        :param string key:
            The entry to retrieve from a file in PYFARM_ETC.  Format is:
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
        """
        # if the key exists in the local preference
        # then use it instead of loading from the file
        if key in Preferences.PREFERENCES:
            return Preferences.PREFERENCES[key]

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

        # keys that will never resolve on their own but are provided for
        # convenience
        if key == 'database.urls':
            return self.__dburl()

        elif key == 'jobtypes.extensions':
            return self.__extensions(kwargs)

        elif key == 'database.setup.config' and os.environ.get('PYFARM_DB_CONFIG'):
            return [ i.split() for i in os.environ.get('PYFARM_DB_CONFIG').split(',') ]

        elif key == 'filesystem.root':
            return self.get('filesystem.roots.%s' % OSNAME)

        # traverse the values and retrieve the data
        try:
            value = name
            data = self.data[name]
            for value in values[1:]:
                data = data[value]

        except KeyError:
            error =  "could not find '%s' data when searching %s" % (value, key)
            raise KeyError(error)

        if key == 'jobtypes.search-paths':
            data = self.__jobtypeSearchPaths(data, kwargs)

        elif key.startswith('filesystem.locations.'):
            data = self.__loggingLocations(data, kwargs)

        elif key == 'jobtypes.path':
            data = self.__expandSearchPaths(data, kwargs)

        elif key == 'host.max-jobs':
            data = self.__calculateMaxJobs(data, kwargs)

        elif key == 'database.setup.config' and isinstance(data, (str, unicode)):
            data = [data]

        Preferences.PREFERENCES[key] = data
        return data
    # end get
# end Preferences

prefs = Preferences()
