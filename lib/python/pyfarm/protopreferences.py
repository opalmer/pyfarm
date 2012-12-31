# No shebang line, this module is meant to be imported
#
# This file is part of PyFarm.
# Copyright (C) 2008-2013 Oliver Palmer
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
import string
import psutil
import tempfile
import warnings
from itertools import imap, chain, ifilter
from os.path import expandvars, expanduser, isdir

from pyfarm import PYFARM_ETC, PYFARM_ROOT, fileio, errors
from pyfarm.datatypes.enums import OperatingSystem
from pyfarm.logger import Logger
from pyfarm.datatypes.system import OS, OSNAME

logger = Logger(__name__)
osname = OSNAME.lower()

if not os.path.isdir(PYFARM_ETC):
    raise OSError("configuration directory does not exist: %s" % PYFARM_ETC)

class PreferenceKeyError(KeyError):
    '''raised if we failed to find a key in the preference file'''
    def __init__(self, key):
        filename = Preferences._filepath(key)
        message = "failed to find '%s' after loading %s" % (key, filename)
        super(PreferenceKeyError, self).__init__(message)
        self.filename = filename
        self.key = key
    # end __init__
# end PreferenceKeyError


class PreferencesFunctions(object):
    '''
    container class which has functions to mix in with the
    preferences class
    '''
    dbconfigs = {}

    @classmethod
    def dburl(cls, name):
        if name in cls.dbconfigs:
            return cls.dbconfigs[name]

        config = cls.get('database.%s' % name)

        data = {
            'driver' : config.get('driver'),
            'engine' : config.get('engine'),
            'dbname' : config.get('name'),
            'dbuser' : config.get('user'),
            'dbpass' : config.get('pass'),
            'dbhost' : config.get('host'),
            'dbport' : config.get('port'),
        }

        if data['driver'] == "psycopg2ct":
            # One last check before we do anything else.  If we happen to be
            # using the ctypes version of the psycopg2 driver then we need
            # it in sys.modules (this is here mainly for some pypy support)
            try:
                from psycopg2ct import compat
                compat.register()

            except ImportError:
                pass

            data['driver'] = 'psycopg2'

        # for sqlite disregard any additional database information
        # and warn
        if data['engine'] == 'sqlite':
            # TODO: conditionally emit this warning, we may use sqlite for some
            # things in the future
            warnings.warn(
                "sqlite is only supported for testing purposes",
                RuntimeWarning
            )
            url = "%(engine)s://%(dbhost)s"
        elif data['driver'] is None:
            url = "%(engine)s://%(dbuser)s:%(dbpass)s@%(dbhost)s"
        else:
            url = "%(engine)s+%(driver)s://%(dbuser)s:%(dbpass)s@%(dbhost)s"

        if isinstance(data['dbport'], int) and data['engine'] != 'sqlite':
            url += ":%(dbport)s"

        return url % data
    # end dburl

    @classmethod
    def post_database_setup_config(cls, data):
        '''
        constructs the final database configuration data for
        the "database.setup.configs" key

        Input Data:
            >>> {"default" : ["db-sqlite"]}

        Output Data:
            >>> {"default" : [("db-sqlite", "sqlite:///:memory:")]}
        '''
        # iterate all configuration data and resolve
        # the database url
        for name, configs in data.iteritems():

            for index, config in enumerate(configs):
                dburl = cls.dburl(config)
                data[name][index] = (config, dburl)

        return data
    # end post_database_setup_configs

    @classmethod
    def post_jobtypes_path(cls, data):
        '''
        converts and expands any paths provided by the preferences
        into a list containing fully qualified entries
        '''
        root = cls.get('filesystem.roots.%s' % osname)
        replaceroot = lambda path: string.Template(path).safe_substitute({'root' : root})
        results = list(
            ifilter(isdir, imap(replaceroot, chain(*imap(expandpath, data))))
        )

        # one last iteration to make sure we don't
        # return duplicate paths
        output = []
        for path in results:
            if path not in output:
                output.append(path)

        return output
    # end post_jobtypes_path
# end PreferencesFunctions


class Preferences(PreferencesFunctions):
    '''
    Preferences object which handles loading of configuration
    files and handling of specific special case (such as logging
    directory strings)
    '''
    data = {}
    extension = "%syml" % os.extsep
    notset = "value_not_set"

    @classmethod
    def _filedata(cls, filename):
        '''
        loads data from the requested file or returns
        cached data

        :exception OSError:
            raised if the requested file does not exist on disk
        '''
        if filename in cls.data:
            return cls.data[filename]

        filepath = cls._filepath(filename)

        try:
            cls.data[filename] = fileio.yml.load(filepath)
            return cls.data[filename]

        except OSError:
            raise OSError("configuration does not exist: %s" % filepath)
    # end _filedata

    @classmethod
    def _filepath(cls, filename):
        '''
        constructs the full file path when given either the
        name of a file or a preference key
        '''
        filename = filename.split(".")[0] if "." in filename else filename
        return os.path.join(PYFARM_ETC, filename + cls.extension)
    # end _filepath

    @classmethod
    def _function(cls, key, mode):
        if mode not in ('pre', 'post'):
            raise ValueError("method must be either a pre or post")

        function_name = "%s_" % mode + key.replace(".", "_").replace("-", "_")
        function = getattr(cls, function_name, None)
        return function if callable(function) else None
    # end _getmethod

    @classmethod
    def get(cls, key):
        if key in cls.data:
            return cls.data[key]

        # convert the incoming key to a function name
        # in case there's some additional logic to run
        function = cls._function(key, 'pre')
        if function is not None:
            result = function()
            cls.data[key] = result
            return cls.data[key]

        # split the incoming key into the filename
        # then using the remaining portion of the
        # key to retrieve the data
        split = key.split(".")
        filename = split[0]
        result = cls._filedata(filename)
        keys = [filename]

        # iterate over the last bit
        # of the key and retrieve the data
        for subkey in split[1:]:
            keys.append(subkey)
            try:
                result = result[subkey]
            except KeyError:
                raise PreferenceKeyError(".".join(keys))

        function = cls._function(key, 'post')
        cls.data[key] = function(result) if function is not None else result
        return cls.data[key]
    # end get
# end Preferences

prefs = Preferences()
expandpath = lambda path: expandvars(expanduser(path)).split(os.pathsep)
