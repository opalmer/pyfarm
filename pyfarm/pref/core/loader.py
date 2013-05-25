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
import pprint
from os.path import isdir, isfile
from UserDict import IterableUserDict

import appdirs
import yaml

try:
    from yaml import CLoader as YAMLLoader
except ImportError:
    from yaml import Loader as YAMLLoader

from pyfarm import __version__
from pyfarm.logger import Logger
from pyfarm.pref.core.enums import NOTFOUND
from pyfarm.pref.core.errors import (
    EmptyPreferenceError, PreferenceLoadError
)

logger = Logger(__name__)

class Loader(IterableUserDict):
    """
    The base loader class for all individual preference files.  By default
    this class will search several directories including the user directory,
    site directory, and PyFarm's own internal configuration directory.  For
    more information on how each of these paths are resolved see these
    individual functions:

        * :py:func:`appdirs.user_data_dir`
        * :py:func:`appdirs.site_data_dir`

    For PyFarm's configuration we look to py:attr:`pyfarm.PYFARM_ETC` which
    is constructed either off of $PYFARM_ETC from the environment or
    off of PyFarm's root path.

    :param string filename:
        The name of the file to load without the extension.

    :param boolean force:
        if True then force reload the preference file(s)

    :exception PreferenceLoadError:
        Raised if we failed to find any preference files by the given name or
        if we failed to find any preference directories
    """
    DATA = {}     # contains previous data loaded in a formatted form
    FILEDATA = {} # contains specific data per file

    # Configuration data which contains the
    # directories where we will load user defined
    # preferences from
    config = appdirs.AppDirs("pyfarm", "pyfarmdev")

    # List of directories where we will search for preferences.  As an
    # example the default configuration will list three entries:
    #   0.4.0
    #   0.4
    #   default
    versions = (
        ".".join(map(str, __version__)),
        ".".join(map(str, __version__[0:2])),
        ""
    )

    # All possible root directors where we should expect to find
    # preferences.  Please note that we do not filter this list
    # here since a long running process may later have access to a
    # missing directory.
    configdirs = (
        config.user_data_dir,
        config.site_data_dir,
        PYFARM_ETC
    )

    extension = "%syml" % os.path.extsep
    joinargs = lambda self, items: os.path.join(*items)

    def __init__(self, filename, force=False):
        data = {}
        self.name = filename

        # ensure we're being provided a string
        if not isinstance(filename, (str, unicode)):
            raise TypeError("filename must be a string")
        else:
            filename = "%s%s" % (filename, self.extension)

        self.filename = filename

        # create a list of possible configuration directories
        dirnames = []
        for version in self.versions:
            for dirname in self.configdirs:
                path = os.path.join(dirname, version)
                dirnames.append(path)

        # Filter the directory list down to only places
        # which actually exist.  Raise an ex
        self.dirnames = filter(isdir, dirnames)
        if not self.dirnames:
            msg = "no preference directories were found after "
            msg += "trying %s" % pprint.pformat(dirnames)
            raise PreferenceLoadError(msg)

        # now create a list of possible files
        joinfile = lambda root : os.path.join(root, filename)
        all_filenames = map(joinfile, self.dirnames)
        filenames = filter(isfile, all_filenames)

        if not filenames:
            msg = "failed to find any preference files after "
            msg += "trying %s" % pprint.pformat(all_filenames)
            raise PreferenceLoadError(msg)

        self.filenames = []
        for filepath in reversed(filenames):
            try:
                yamldata = self.load(filepath, force=force)
                self.validate(filepath=filepath, filedata=yamldata)

            except EmptyPreferenceError: # skip empty files
                logger.warning("%s does not contain data" % filepath)
                continue

            except Exception, error: # relog any unhandled exceptions
                logger.error("error while loading %s: %s" % (filepath, error))
                continue

            # if the preference loaded then append
            # it to our filename list and update our internal data
            self.filenames.append(filepath)
            data.update(yamldata)

        # if we still don't have any files in self.filenames
        # then we have a problem and should raise and error
        if not data:
            raise PreferenceLoadError(
                "failed to find or load data for %s" % filename
            )

        IterableUserDict.__init__(self, data)
        self.validate()
    # end __init__

    def __repr__(self):
        values = [
            "%s=%s" % (key, repr(value)) for key, value in self.iteritems()
        ]
        return "%s(%s)" % (self.__class__.__name__, ", ".join(values))
    # end __repr__

    def __getitem__(self, item):
        """
        override of :meth:`IterableUserDict.__getitem__` that allows for
        uri access
        """
        try:
            return IterableUserDict.__getitem__(self, item)
        except KeyError:
            # if a sep was not provided then we should reraise
            # the exception
            if "." not in item:
                raise
            else:
                # otherwise iterate over the incoming data and
                # use each key to retrieve the data we need but
                # don't catch exceptions
                data = self.data
                visisted = []
                for entry in item.split("."):
                    visisted.append(entry)
                    try:
                        data = data[entry]
                    except KeyError:
                        args = (".".join(visisted), self.filenames)
                        msg = "failed to find %s in %s" % args
                        raise KeyError(msg)

                return data
    # end __getitem__

    @classmethod
    def load(cls, filepath, force=False):
        """
        Loads data for the requested file path or returns existing data if the
        file has already been loaded once.

        :exception EmptyPreferenceError:
            raised if the the preference file that was loaded is empty
        """
        if force or filepath not in cls.FILEDATA:
            # open a stream and load the data but raise and exception
            # if we did not find any data
            with open(filepath, 'r') as stream:
                logger.info("loading %s" % filepath)
                cls.FILEDATA[filepath] = yaml.load(stream, Loader=YAMLLoader)

                if not cls.FILEDATA[filepath]:
                    raise EmptyPreferenceError(
                        "%s does not contain data" % filepath
                    )

        return cls.FILEDATA[filepath]
    # end load

    def where(self, key, all=False):
        """
        Returns the filename where the preference is defined.  Depending
        on the implementation of the final preference object this
        method may be overridden in a subclass to provide more accurate
        results.

        :param string key:
            the string we're trying to find the location for

        :param boolean all:
            if True find all locations the key is defined in

        :exception KeyError:
            raised if the key we are requesting does not exist in self.data

        :exception ValueError:
            raised if we could not find the original file which
            defined the requested key

        :returns:
            returns the filename where the key is defined or a list
            of files where the key is defined if all was True
        """
        if key not in self:
            raise KeyError("key %s does not exist" % repr(key))

        # iterate over all files used to create this
        # loader object and find the first one which has
        # data matching the current data
        results = []

        for filename in self.filenames:
            if not all and self[key] == self.FILEDATA[filename].get(key, NOTFOUND):
                return filename

            elif all and key in self.FILEDATA[filename]:
                results.append(filename)

        if not results:
            raise ValueError(
                "failed to determine where '%s' came from" % repr(key)
            )
        else:
            results.reverse()
            return results
    # end where

    def validate(self, filepath=None, filedata=None):
        """
        Validation method which is run for each file loaded and after all
        files have loaded.  By default this method does nothing.

        :param string filepath:
            the filepath of the current file being loaded

        :param dictionary filedata:
            the data of the file currently being loaded
        """
        pass
    # end validation

    def get(self, key, failobj=None):
        """
        override of :meth:`IterableUserDict.get` that allows for
        uri access
        """
        try:
            return self.__getitem__(key)
        except KeyError:
            return failobj
    # end get
# end Loader
