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
General utility functions that are not specific to individual components
of PyFarm.
"""

import os
import sys
import getpass
from tempfile import NamedTemporaryFile
from pyfarm.files.path import expandenv

PyMajor, PyMinor, PyMicro = sys.version_info[0:3]

def which(program):
    """
    returns the path to the requested program

    .. note::
        This function will not resolve aliases

    :raise OSError:
        raised if the path to the program could not be found
    """
    # Returns the full path to the requested program.  If the path
    # is in fact valid then we have nothing left to do.
    fullpath = os.path.abspath(program)
    if os.path.isfile(fullpath):
        return fullpath

    for path in expandenv('PATH', validate=True):
        fullpath = os.path.join(path, program)
        if os.path.isfile(fullpath):
            return fullpath

    # if all else fails, fail
    args = (program, os.environ.get('PATH'))
    raise OSError("failed to find program '%s' in %s" % args)
# end which

def user():
    """returns the current user name"""
    try:
        import pwd
        return pwd.getpwuid(os.getuid())[0]

    except ImportError:
        return getpass.getuser()
# end user

def tempfile(prefix=None, suffix=None, delete=False):
    """
    A wrapper around :py:class:`tempfile.NamedTemporaryFile` which ensures that
    Python versions before 2.6 will respect the delete keyword.

    :param boolean delete:
        if True then delete the file after closing

    :returns:
        returns a temp file object
    """
    # construct arguments to pass to the constructor of
    # NamedTemporaryFile
    kwargs = {
        'prefix' : 'pyfarm-' if prefix is None else prefix,
        'suffix' : suffix if suffix is not None else ''
    }

    if (PyMajor, PyMicro) >= (2, 6):
        kwargs['delete'] = delete
        return NamedTemporaryFile(**kwargs)

    elif delete:
        return NamedTemporaryFile(**kwargs)

    else:
        _stream = NamedTemporaryFile(**kwargs)
        _stream.close()
        return open(_stream.name, 'w')
# end tempfile
