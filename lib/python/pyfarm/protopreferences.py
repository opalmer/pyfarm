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
import pprint
import appdirs
from os.path import isfile, isdir
from itertools import product

import yaml
try:
    from yaml import CLoader as YAMLLoader
except ImportError:
    from yaml import Loader as YAMLLoader

from pyfarm import __version__, PYFARM_ETC
from pyfarm.logger import Logger

logger = Logger(__name__)

from UserDict import IterableUserDict


class Loader(IterableUserDict):
    '''
    The base loader class for all individual preference files.  By default
    this class will search several directories including the user directory,
    site directory, and PyFarm's own internal configuration directory.  For
    more information on how each of these paths are resolved see these
    individual functions:

        * :py:func:`appdirs.user_data_dir`
        * :py:func:`appdirs.site_data_dir`
        * :py:func:`appdirs.site_data_dir`

    For PyFarm's configuration we look to py:attr:`pyfarm.PYFARM_ETC` which
    is constructed either off of $PYFARM_ETC from the environment or
    off of PyFarm's root path.

    :param string filename:
        The name of the file to load without the extension.

    :param list data:
        If provided then this value will be use to populate self.data
        and self._data.  Please note that :py:class:`Loader` expects
        the data to be a list of tuples:
            >>> [('fileA.yml', {'A' : True}), ('fileB.yml', {'B' : True})]

    :exception OSError:
        raised if we failed to find any preference files by the given name

    :exception ValueError:
        raised if preference files were found but did not contain any
        data
    '''
    DATA = {}

    # Configuration data which contains the
    # directories where we will load user defined
    # preferences from
    config = appdirs.AppDirs(
        "pyfarm",
        "Oliver Palmer"
    )

    # List of directories where we will search for preferences.  As an
    # example the default configuration will list three entries:
    #   0.4.0
    #   0.4
    #   default
    versions = (
        ".".join(map(str, __version__)),
        ".".join(map(str, __version__[0:2])),
        "default"
    )

    # all possible root directors
    dirnames = (
        config.user_data_dir,
        config.site_data_dir,
        PYFARM_ETC
    )

    extension = "%syml" % os.path.extsep
    joinargs = lambda self, items: os.path.join(*items)

    def __init__(self, filename=None, data=None):
        # filename setup
        if isinstance(filename, (str, unicode)):
            self.filename = "%s%s" % (filename, self.extension)
        else:
            self.filename = None

        if self.filename is not None and data is None:
            # if the requested file name has not been loaded
            # yet the consturct the data
            if self.filename not in self.DATA:
                tofilepath = lambda root: os.path.join(root, self.filename)

                # Construct a complete list of tuple containing all combinations
                # of directory names and versions.  After doing to filter
                # that list to directories which actually exist.
                products = product(self.dirnames, self.versions)
                preference_dirs = filter(isdir, map(self.joinargs, products))

                # Now construct a list of possible preference files and
                # filter them into a tuple of files which actually exists.
                all_preference_files = map(tofilepath, preference_dirs)
                filenames = tuple(filter(isfile, all_preference_files))

                # raise an exception if we did not find any files by that name
                if not filenames:
                    args = (self.filename, pprint.pformat(preference_dirs))
                    raise OSError("did not find %s in %s" % args)

                # iterate over each file found and update our
                # current data with data from the files
                data = []
                for filename in filenames:
                    with open(filename, 'r') as stream:
                        logger.debug("loading %s" % filename)
                        data.append((
                            filename, yaml.load(stream, Loader=YAMLLoader)
                        ))

                if not data:
                    raise ValueError(
                        "preferences files not populated for %s" % self.filename
                    )

                self._data = self.DATA[self.filename] = tuple(reversed(data))

            # if we've already pull the preferences for the provided
            # filename then just set the data
            else:
                self._data = self.DATA[self.filename]

        # full data was provided
        elif data is not None:
            self._data = tuple(data) if isinstance(data, (list, set)) else data

        # construct the resulting data
        self.reloadata()

        IterableUserDict.__init__(self, self.data)
    # end __init__

    def reloadata(self):
        '''
        Reloads the internal data attribute with data from _data.
        '''
        self.data = {}
        for filepath, data in self._data:
            self.data.update(data)
    # end initdata

    def where(self, key):
        '''
        Returns the filename where the preference is defined.  Depending
        on the implementation of the final preference object this
        method may be overridden in a subclass to provide more accurate
        results.

        >>> l1 = Loader(data=[("fileA.yml", {"A" : True, "B" : True},)])
        >>> assert l1.where("A") == "fileA.yml"
        >>> assert l1.where("B") == "fileA.yml"

        :param string key:
            the string we're trying to find the location for

        :exception KeyError:
            raised if the key we are requesting does not exist in self.data

        :returns:
            returns the filename where the preference is defined
        '''
        if key not in self:
            raise KeyError("key %s does not exist" % key)
        else:
            current_value = self[key]
            for filename, data in self._data:
                if key in data and data[key] == current_value:
                    return filename
    # end where

    def __add__(self, other):
        '''
        Adds two :py:class:`Loader` objects together, so long as they have not
        loaded the same filename, and returns a new object.

        >>> l1 = Loader(data=[("fileA.yml", {"A" : True, "C" : True},)])
        >>> l2 = Loader(data=[("fileB.yml", {"A" : False, "B" : None},)])
        >>> l3 = l1 + l2
        >>> assert l3.get('A') == False
        >>> assert l3.filename is None
        >>> assert l3.where("A") == "fileB.yml"
        >>> assert l3.where("B") == "fileB.yml"
        >>> assert l3.where("C") == "fileA.yml"

        :exception ValueError:
            raised if the two objects have the same filename

        :exception TypeError:
            raised of the other object is not a :py:class:`Loader` object
        '''
        if not isinstance(other, Loader):
            raise TypeError("other object must be a loader")

        if self.filename is not None and self.filename != other.filename:
            raise ValueError("will not add two objects with the same filename")

        new_data = list(self._data)
        new_data.extend(list(other._data))
        return Loader(data=new_data)
    # end __add__

    def __iadd__(self, other):
        '''
        Adds another :py:class:`Loader` to this object so long as they have
        not loaded the same filename.

        :exception ValueError:
            raised if the two objects have the same filename

        :exception TypeError:
            raised of the other object is not a :py:class:`Loader` object
        '''
        if not isinstance(other, Loader):
            raise TypeError("other object must be a loader")

        if self.filename is not None and self.filename != other.filename:
            raise ValueError("will not add two objects with the same filename")

        new_data = list(self._data)
        new_data.extend(other._data)
        self._data = tuple(new_data)
        self.reloadata()
    # end __iadd__
# end Loader


class Preferences(object):
    DATA = {}

    def __init__(self):
        pass

    @classmethod
    def get(cls, name, alt=None):
        split = name.split(".")
        filename = split[0]

        # load the underlying data if necessary, retrieve it from
        # cace otherwise
        if filename not in cls.DATA:
            data = cls.DATA[filename] = Loader(filename)
        else:
            data = cls.DATA[filename]

        # simply return the data if the filename
        # we found was the same as the key name
        if filename == name:
            return data
    # end get
# end Preferences
