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
import tempfile
from StringIO import StringIO

# get the fastest loader
try:
    from yaml import CLoader as YAMLLoader
except ImportError:
    from yaml import Loader as YAMLLoader

from yaml import load as _loadyaml, dump as _dumpyaml

from pyfarm.files.path import tempdir


class TempFile(file):

    """
    Similar to :func:`tempfile.NamedTemporaryFile` except it takes
    advantage of PyFarm's :func:`.tempdir` function and the `delete` keyword
    will work in Python 2.5.

    .. note::
        when/if support is dropped for Python 2.5 this class will remain
        but instead directly use :func:`tempfile.NamedTemporaryFile`
    """
    def __init__(self, name=None, suffix=None,
                 mode='w', buffering=-1, root=None, delete=False):
        self.__delete = delete

        # setup our root directory and filepath
        dirname = tempdir() if root is None else root
        fd, filepath = tempfile.mkstemp(dir=root, suffix=suffix)

        if name is not None and suffix is not None:
            raise ValueError(
                "name and suffix cannot be provided at the same time"
            )

        elif name is not None:
            name = os.path.join(os.path.dirname(filepath), name)
        else:
            name = filepath

        super(TempFile, self).__init__(name, mode=mode, buffering=buffering)
    # end __init__

    def close(self):
        """
        After calling :meth:`file.close` check to see if the `delete` keyword
        was set in :meth:`__init__` and if so remove the file from disk
        """
        super(TempFile, self).close()
        if self.__delete:
            os.remove(self.name)
    # end close
# end TempFile


def ymlload(stream):
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


def ymldump(data, stream=None, pretty=False):
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
        stream = TempFile(suffix='.yml')
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
# end ymldump
