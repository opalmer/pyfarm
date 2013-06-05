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
import shutil
import tempfile
from StringIO import StringIO

try:
    from yaml import CLoader as YAMLLoader
except ImportError:
    from yaml import Loader as YAMLLoader

from yaml import load as _yamlLoad, dump as _yamlDump

from pyfarm.ext.files.path import tempdir


class TempFile(file):

    """
    Similar to :func:`tempfile.NamedTemporaryFile` except it takes
    advantage of PyFarm's :func:`.tempdir` function and the `delete` keyword
    will work in Python 2.5.

    .. note::
        when/if support is dropped for Python 2.5 this class will remain
        but instead directly use :func:`tempfile.NamedTemporaryFile`
    """
    def __init__(self, prefix=None, suffix=None,
                 mode='w', buffering=-1, root=None, delete=False):
        self.__delete = delete

        # setup our root directory and filepath
        root = tempdir() if root is None else root
        suffix = "" if suffix is None else suffix
        prefix = "" if prefix is None else prefix
        fd, filepath = tempfile.mkstemp(prefix=prefix, suffix=suffix, dir=root)
        super(TempFile, self).__init__(filepath, mode=mode, buffering=buffering)

    def close(self):
        """
        After calling :meth:`file.close` check to see if the `delete` keyword
        was set in :meth:`__init__` and if so remove the file from disk
        """
        super(TempFile, self).close()
        if self.__delete:
            os.remove(self.name)


def yamlLoad(source):
    """
    Loads data from the provided file stream, stream like object, or file
    path.

    :type source: str or :py:class:`StringIO.StringIO` or file
    :param source:
        The object or path to load data from

    :exception TypeError:
        raised if we get an unexpected type for `stream`
    """
    if isinstance(source, basestring):
        source = open(source, 'r')

    elif not isinstance(source, (file, StringIO)):
        raise TypeError("expected a filepath, file, or StringIO object")

    try:
        return _yamlLoad(source, Loader=YAMLLoader)

    finally:
        source.close()


def yamlDump(data, path=None, pretty=False):
    """
    dumps data to the requested file path

    :param data:
        The data we're attempting to dump.  The type input to this
        parameter can be anything yaml itself can normally handle.

    :param str path:
        the path to dump the yaml file to

    :param bool pretty:
        if True then dump the data in a more human readable form

    :returns:
        returns the path we dumped the data to
    """
    if path is None:
        stream = TempFile(suffix=".yml", delete=False)

    elif isinstance(path, basestring):
        dirname = os.path.dirname(path)
        if not os.path.isdir(dirname):
            os.makedirs(dirname)

        stream = open(path, "w")

    elif isinstance(path, (file, StringIO)):
        stream = path

    elif not isinstance(path, basestring):
        raise TypeError("expected a string for `path`")

    # arguments to pass to the dumper
    args = [data, stream]
    kwargs = {"default_flow_style": False, "indent": 4} if pretty else {}

    try:
        _yamlDump(*args, **kwargs)
        return stream.name

    finally:
        stream.close()

# Placeholders that are called internally
# so they can be overridden by an extension.
makedirs = os.makedirs
remove = os.remove
chown = os.chown
chmod = os.chmod
chdir = os.chdir
rmtree = shutil.rmtree
copy = shutil.copy
copy2 = shutil.copy
copytree = shutil.copytree