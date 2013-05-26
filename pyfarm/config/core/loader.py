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

import yaml
try:
    from yaml import CLoader as _YAMLLoader
except ImportError:
    from yaml import Loader as _YAMLLoader

from pyfarm.config.core import find
from pyfarm.config.errors import (
    SubKeyError, PreferenceLoadError, PreferencesNotFoundError
)


class Loader(IterableUserDict):
    """
    Objects responsible for loading configuration files from disk
    and caching the results.
    """
    __configdata__ = {}

    def __init__(self, filename, data=None):
        data = {} if data is None else data
        assert isinstance(data, dict), "data should be a dictionary object"
        self.files = find.configFiles(filename)

        if not self.files:
            raise PreferencesNotFoundError(
                "failed to find any preference files for %s" % filename
            )

        # load data from each file
        for filepath in self.files:
            data.update(self._load(filepath))

        self.__instanced = False
        IterableUserDict.__init__(self, data)
        self.__instanced = True
    # end __init__

    @classmethod
    def _load(cls, filepath):
        """underlying method to load data from a yaml file path"""
        if filepath not in cls.__configdata__:
            with open(filepath, 'r') as stream:
                try:
                    data = yaml.load(stream, Loader=_YAMLLoader)
                    cls.__configdata__[filepath] = data

                except Exception, e:
                    raise PreferenceLoadError(
                        "failed to load %s: %s" % (stream.name, e)
                    )

        return cls.__configdata__[filepath]

    def which(self, key):
        """determine which file defined the 'default' value for the given key"""
        return self.where(key)[-1]
    # end which

    def where(self, key):
        """determine where the value for the provided key was defined"""
        paths = []
        for path in self.files:
            if key in self.__configdata__[path]:
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
                raise

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
        if self.__instanced:
            pass
        return IterableUserDict.update(self, dict, **kwargs)
    # end update

    def __setitem__(self, key, value):
        # TODO: add warning message
        if self.__instanced:
            pass
        return IterableUserDict.__setitem__(self, key, value)
    # end __setitem__
# end Loader