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
#

"""
Internal yaml module which wraps around :py:func:`yaml.load` and
:py:func:`yaml.dump`.
"""

from __future__ import with_statement

import os
from StringIO import StringIO

from pyfarm.utility import tempfile
from pyfarm.logger import Logger

# get the fastest loader
try:
    from yaml import CLoader as Loader
except ImportError:
    from yaml import Loader

# get the underlying functions we'll use for loading
# and dumping
from yaml import load as _load, dump as _dump

logger = Logger(__name__)

def load(stream):
    """
    Loads data from the provided file stream, stream like object, or file
    path.

    :type stream: str or :py:class:`StringIO.StringIO` or file
    :param stream:
        The object or path to load data from

    :exception TypeError:
        raised if we get an unexpected type for `stream`
    """
    if isinstance(stream, basestring) and os.path.isfile(stream):
        logger.info("loading yaml from %s" % stream)
        stream = open(stream, 'r')

    elif not isinstance(stream, file) and not isinstance(stream, StringIO):
        msg = "Expected stream to be a file path, file object, or"
        msg += "StringIO instance.  Got %s instead" % type(stream)
        raise TypeError(msg)

    else:
        logger.info("loading yaml from %s" % stream)

    # load and return data from the stream and
    # be sure the close the stream afterwards
    try:
        return _load(stream, Loader=Loader)

    finally:
        if callable(getattr(stream, 'close', None)):
            stream.close()
# end load

def dump(data, stream=None, pretty=False):
    """
    Dumps data to the requested stream if provided or a temporary file.

    :param data:
        the data we are attempting to dump

    :type stream: str or :py:class:`StringIO.StringIO` or file
    :param stream:
        the

    :param boolean pretty:
        if True then dump the data in a more human readable form

    :returns:
        returns the path or object the data was dumped to
    """
    if stream is None:
        stream = tempfile(suffix='.yml')
        return_object = stream.name

    elif isinstance(stream, basestring):
        stream = open(stream, 'w')
        return_object = stream.name

    else:
        return_object = stream

    # construct arguments to pass along
    # to the yaml dumper
    args = [data, stream]
    if pretty:
        kwargs = {
            'default_flow_style' : False,
            'indent' : 4
        }
    else:
        kwargs = {}

    try:
        logger.debug("dumping yaml data to %s" % return_object)
        _dump(*args, **kwargs)
        return return_object

    finally:
        closeable = callable(getattr(stream, 'close', None))
        if return_object is not stream and closeable:
            stream.close()
# end dump
