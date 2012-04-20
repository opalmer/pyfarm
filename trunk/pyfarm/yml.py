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

'''read and write wrappers for PyYaml'''

from __future__ import with_statement

import os
import yaml
from twisted.python import log
from pyfarm import logger

YAML_LOADER = None
YAML_WRITER = None
LOADED = {}

# setup the loader
if hasattr(yaml, 'CLoader'):
    YAML_LOADER = yaml.CLoader
else:
    YAML_LOADER = yaml.Loader

if hasattr(yaml, 'CDumper'):
    YAML_DUMPER = yaml.CDumper
else:
    YAML_DUMPER = yaml.Dumper

log.msg("loader: %s, writer: %s" % (YAML_LOADER, YAML_DUMPER), system="PyYaml")

def load(path, force=False):
    '''
    loads data from the provided path.

    :param boolean force:
        if True the reload the data from disk
    '''
    abspath = os.path.abspath(path)

    if force or abspath not in LOADED:
        with open(path, 'r') as stream:
            LOADED[abspath] = yaml.load(stream, Loader=YAML_LOADER)

    return LOADED[abspath]
# end load

def dump(data, path=None):
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
        yaml.dump(data, stream, Dumper=YAML_DUMPER)

    return path
# end dump