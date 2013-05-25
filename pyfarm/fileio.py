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

"""
module which contains functions for loading information
from disk
"""

import imp

try:
    from yaml import CLoader as Loader, CDumper as Dumper
except ImportError:
    from yaml import Loader, Dumper

from pyfarm.logger import Logger

logger = Logger(__name__)

class module:
    CACHE = {}

    @classmethod
    def load(cls, name, paths, namespace=None, force=False):
        """
        loads the given module from the provided path(s)

        :param string name:
            name of the module to load

        :param string or list paths:
            the path or paths to search for the module in

        :param boolean force:
            if True then bypass the cache
        """
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
            logger.debug("loaded %s" % stream.name)

        return cls.CACHE[namespace]
