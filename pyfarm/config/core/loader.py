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

from UserDict import IterableUserDict

from pyfarm.files.file import yamlLoad
from pyfarm.config.core import find
from pyfarm.config.core.errors import (
    SubKeyError, PreferenceLoadError, PreferencesNotFoundError
)


class Loader(IterableUserDict):
    """
    Objects responsible for loading configuration files from disk
    and caching the results.

    :type filename: str
    :param filename:
        the name of the file to find (ex.

    :type data: dict
    :param data:
        initial data to load into :class:`Loader`

    :type cached: bool
    :param cached:
        if True then load data from the cache if we've already created
        a loader by `filename`

    :type load: bool
    :param load:
        if True then load in all files by `filename` using
        :func:`find.configFiles`

    :param findkwargs:
        any additional keywords to pass along to :func:`find.configFiles`
    """
    _DATA = {}
    FILENAME = None

    def __init__(self, filename=None, data=None, cached=True, load=True,
                 **findkwargs):
        data = {} if data is None else data
        filename = self.FILENAME or filename
        assert isinstance(data, dict), "data should be a dictionary object"
        assert isinstance(filename, basestring), "filename not resolved"

        if load:
            self.files = find.configFiles(filename, **findkwargs)

            if not self.files:
                raise PreferencesNotFoundError(
                    "failed to find any preference files for %s" % filename
                )

            # load data from each file
            for filepath in self.files:
                data.update(self._load(filepath, cached=cached))
        else:
            self.files = []

        self.__instanced = False
        IterableUserDict.__init__(self, data)
        self.__instanced = True
    # end __init__

    @classmethod
    def _load(cls, filepath, cached=True):
        """
        Underlying classmethod to load data from a yaml path.  This
        classmethod will raise a :class:`PreferenceLoadError` for
        any exception thrown while loading the file.
        """
        if not cached or filepath not in cls._DATA:
            try:
                data = yamlLoad(filepath)
                cls._DATA[filepath] = data

            except Exception, e:
                raise PreferenceLoadError(
                    "failed to load %s: %s" % (filepath, e)
                )

        return cls._DATA[filepath]

    def which(self, key):
        """
        Determine which file defined the 'default' value for the given
        key.  This returns `None` if the key could not be found.
        """
        try:
            return self.where(key)[-1]
        except IndexError:
            return None
    # end which

    def where(self, key):
        """determine where the value for the provided key was defined"""
        paths = []
        for path in self.files:
            if key in self._DATA[path]:
                paths.append(path)
        return paths
    # end where

    def get(self, key, failobj=None):
        """
        Get the requested key from the underlying data.  This will
        first try `self[data]` before attempting to decompose the key
        based on `.` and diving down into the dictionary
        structure.

        :exception TypeError:
            raised if `key` is not a string

        :exception KeyError:
            raised if a key is requested but it does not
            contain '.' and does not exist in data

        :exception SubKeyError:
            raised if we did not find a part of the subkey request
        """
        if not isinstance(key, basestring):
            raise TypeError("expected a string for `key`")

        try:  # this might be a top level key, try to retrieve it
            return self[key]
        except KeyError:
            # it's not a top level key and it's not
            # a key we can break down
            if "." not in key:
                return failobj

            # walk down the key and retrieve the data
            data = self
            for subkey in key.split("."):
                try:
                    data = data[subkey]

                except KeyError:
                    raise SubKeyError(
                        "failed to find subkey '%s' of %s" % (subkey, key)
                    )

            return data
    # end get

    def update(self, dict=None, **kwargs):
        # TODO: add warning message
        if not self.__instanced:
            return IterableUserDict.update(self, dict, **kwargs)
    # end update

    def __setitem__(self, key, value):
        # TODO: add warning message
        if not self.__instanced:
            return IterableUserDict.__setitem__(self, key, value)
    # end __setitem__
# end Loader