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

'''
module which contains functions for loading information
from disk
'''

from __future__ import with_statement

import os
import imp
import yaml
import logging
import tempfile

from twisted.python import log

class yml:
    CACHE = {}
    LOADER = yaml.Loader
    DUMPER = yaml.Dumper

    if hasattr(yaml, 'CLoader'):
        LOADER = yaml.CLoader

    if hasattr(yaml, 'CDumper'):
        DUMPER = yaml.CDumper

    @classmethod
    def load(cls, path, force=False):
        '''
        loads data from the provided path.

        :param boolean force:
            if True the reload the data from disk
        '''
        abspath = os.path.abspath(path)

        if force or abspath not in cls.CACHE:
            with open(path, 'r') as stream:
                cls.CACHE[abspath] = yaml.load(stream, Loader=cls.LOADER)
                log.msg(
                    "Loaded: %s" % abspath, system="loader.yml",
                    level=logging.INFO
                )

        return cls.CACHE[abspath]
    # end load

    @classmethod
    def dump(cls, data, path=None):
        '''
        Dumps data to the requested path or a temp
        file if none is provided

        :param data:
            the data to dump

        :param string path:
            the path to write to or None for a temp path

        :rtype string:
            returns the location the data was dumped to
        '''
        if path is None:
            tempstream = tempfile.NamedTemporaryFile()
            path = tempstream.name
            tempstream.close()

        with open(path, 'w') as stream:
            yaml.dump(data, stream, Dumper=cls.DUMPER)

            return path
    # end dump
# end yml


class module:
    CACHE = {}

    @classmethod
    def load(cls, name, paths, namespace=None, force=False):
        '''
        loads the given module from the provided path(s)

        :param string name:
            name of the module to load

        :param string or list paths:
            the path or paths to search for the module in

        :param boolean force:
            if True then bypass the cache
        '''
        # use the module name itself if one is not provided
        if namespace is None:
            namespace = name

        # ensure paths is a list
        if isinstance(paths, (str, unicode)):
            paths = [paths]

        if force or namespace not in cls.CACHE:
            stream, path, description = imp.find_module(name, paths)
            module = imp.load_module(name, stream, path, description)
            cls.CACHE[namespace] = module

        return cls.CACHE[namespace]