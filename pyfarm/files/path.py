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
import stat
import tempfile
import threading

THREAD_RLOCK = threading.RLock()
SESSION_DIRECTORY = None
DEFAULT_DIRECTORY_PREFIX = os.environ.get("PYFARM_TMP_PREFIX", "pyfarm-")
DEFAULT_PERMISSIONS = stat.S_IRWXU|stat.S_IRWXG


def tempdir(unique=False, respect_env=True, mode=DEFAULT_PERMISSIONS):
    """
    Returns a temporary directory that will exist and have the requested
    permission(s).

    :param int mode:
        the mode to :func:`os.chmod` the directory to, typically this is a
        combination of values from :mod:`stat`

    :param bool respect_env:
        if True then respect `$PYFARM_TMP` (if it's defined)
        instead of creating a path

    :param bool unique:
        if `$PYFARM_TMP` is not provided and this value is True then create and
        return a single directory for the entire Python session.
    """
    global SESSION_DIRECTORY

    if respect_env and "PYFARM_TMP" in os.environ:
        dirname = os.environ["PYFARM_TMP"]

        if not os.path.isdir(dirname):
            os.makedirs(dirname)

        os.chmod(dirname, mode)
        return dirname
    else:
        if unique:
            dirname = tempfile.mkdtemp(prefix=DEFAULT_DIRECTORY_PREFIX)
            os.chmod(dirname, mode)
            return dirname

        else:
            with THREAD_RLOCK:
                if SESSION_DIRECTORY is None:
                    path = tempfile.mkdtemp(prefix=DEFAULT_DIRECTORY_PREFIX)
                    os.chmod(path, mode)
                    SESSION_DIRECTORY = path

                return SESSION_DIRECTORY
# end tempdir


def expandpath(path):
    """
    Expands environment variables and user paths such as `~` in `path`
    using :func:`os.path.expandvars` and :func:`os.path.expanduser`
    """
    return os.path.expanduser(os.path.expandvars(path))
# end expandpath


def expandenv(envvar, validate=True, pathsep=None):
    """
    Takes the environment variable given by `envvar`, splits it into
    multiple paths, then expands/validates them as requested.

    :param bool validate:
        if True then only paths which exist will be returned

    :param str pathsep:
        if provided then use this as the value to split the environment
        variable by, otherwise use `os.pathsep`

    :exception exceptions.EnvironmentError:
        raised if the requested `envvar` does not exist

    :exception exceptions.ValueError:
        raised if the requested `envvar` does exist but does
        not contain any data
    """
    if envvar not in os.environ:
        raise EnvironmentError(
            "requested value `%s` does not exist os.environ" % envvar
        )

    elif not os.environ[envvar]:
        raise ValueError("environment var `%s` does not contain data" % envvar)

    results = []
    pathsep = os.pathsep if pathsep is None else pathsep

    for path in map(expandpath, os.environ[envvar].split(pathsep)):
        if not validate or validate and os.path.exists(path):
            results.append(path)



    return results
# end expandenv


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