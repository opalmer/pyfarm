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
Internal yaml module which wraps around :py:func:`yaml.load` and
:py:func:`yaml.dump`.
"""

from __future__ import with_statement
import os
from StringIO import StringIO

from pyfarm.utility import tempfile

# get the fastest loader
try:
    from yaml import CLoader as YAMLLoader
except ImportError:
    from yaml import Loader as YAMLLoader

# get the underlying functions we'll use for loading
# and dumping
from yaml import load as _loadyaml, dump as _dumpyaml


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
        stream = open(stream, 'r')

    elif not isinstance(stream, file) and not isinstance(stream, StringIO):
        msg = "Expected stream to be a file path, file object, or"
        msg += "StringIO instance.  Got %s instead" % type(stream)
        raise TypeError(msg)

    # load and return data from the stream and
    # be sure the close the stream afterwards
    try:
        return _loadyaml(stream, Loader=YAMLLoader)

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
        kwargs = {'default_flow_style': False, 'indent': 4}
    else:
        kwargs = {}

    try:
        _dumpyaml(*args, **kwargs)
        return return_object

    finally:
        closeable = callable(getattr(stream, 'close', None))
        if return_object is not stream and closeable:
            stream.close()
# end dump
