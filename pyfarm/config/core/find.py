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
Responsible for searching for and finding either directories
or files on disk.
"""

import os
import copy
from itertools import imap
from os.path import join, isfile, isdir

try:
    from itertools import product
except ImportError:
    from pyfarm.backports import product

from appdirs import AppDirs
from pyfarm import __version__, PYROOT

DEFAULT_SUBDIR = "etc"
DEFAULT_USER_PATHS = False
DEFAULT_SYSTEM_PATHS = False
DEFAULT_CONFIG_ROOT = join(PYROOT, "config", "etc")
DEFAULT_VERSION = list(__version__)


def _versionSubdirs(version):
    """
    takes a version list and returns a list of strings which should
    serve as subdirectory names
    """
    assert isinstance(version, list), "expected a list for `version`"
    # generate a list of subdirectories to visit so
    # preference files can be locked off
    version_subdirs = [""]
    version_parts = []

    for v in imap(str, version):
        version_parts.append(v)
        add_x_count = len(version) - len(version_parts)
        version_entry = ".".join(version_parts + ['x'] * add_x_count)
        version_subdirs.append(version_entry)

    # explict version should be first
    version_subdirs.reverse()

    return version_subdirs


def configDirectories(roots=None, user=None, system=None, version=None,
                      must_exist=True):
    """
    generator which emits a list of paths were we should
    search for configuration files

    :type roots: str or list
    :param roots:
        additional root directories to search for preferences in

    :type user: str or list
    :param user:
        The location the user configuration directory exists.  If this
        value is not provided it will be set

    :type system: str or list
    :param system:
        if True then add system data directories as well

    :type version: list
    :param version:
        List containing the version broken down into individual components.
        Some examples of this conversion include:

            * "1.0.0" -> ["1", "0", "0"]
            * "1.0" -> ["1", "0"]

    :type must_exist: bool
    :param must_exist:
        if True then only return directories which exist
    """
    appdirs = AppDirs("pyfarm", "pyfarm")
    version = DEFAULT_VERSION if version is None else version
    rootdirs = copy.copy(roots)

    if roots is None:
        rootdirs = [DEFAULT_CONFIG_ROOT]
    elif isinstance(roots, basestring):
        rootdirs = [roots]

    # setup user
    if user is None:
        user = [os.path.join(appdirs.user_data_dir, DEFAULT_SUBDIR)]

    elif "PYFARM_CFGROOT_USER" in os.environ:
        user = filter(bool, os.environ["PYFARM_CFGROOT_USER"].split(os.pathsep))

    elif isinstance(user, basestring):
        user = [user]

    # setup system
    if system is None:
        system = [os.path.join(appdirs.site_data_dir, DEFAULT_SUBDIR)]

    elif "PYFARM_CFGROOT_SYSTEM" in os.environ:
        system = filter(bool, os.environ["PYFARM_CFGROOT_SYSTEM"].split(os.pathsep))

    elif isinstance(system, basestring):
        system = [system]

    # sanity check before we do anything else
    assert isinstance(version, list), \
        "expected list or tuple for `version`"
    assert isinstance(user, list), \
        "failed to convert %s for `user` into a list" % user
    assert isinstance(system, list), \
        "failed to convert %s for `system` into a list" % system
    assert isinstance(rootdirs, list), \
        "failed to convert %s for `roots` into a list" % rootdirs

    # environment paths before go before our paths
    # NOTE: these are not the same thing as the initial value for `rootdors`
    if "PYFARM_CFGROOT" in os.environ:
        rootdirs[:0] = filter(bool, os.environ["PYFARM_CFGROOT"].split(os.pathsep))

    # followed by the system
    if system:
        rootdirs[:0] = system

    # ... then user paths
    if user:
        rootdirs[:0] = user

    version_subdirs = _versionSubdirs(version)

    paths = []
    for root, versiondir in product(rootdirs, version_subdirs):
        path = os.path.join(root, versiondir) if versiondir else root
        if path not in paths and (not must_exist or isdir(path)):
            paths.append(path)

    return paths


def configFiles(name, roots=None, user=None, system=None, must_exist=True):
    """
    Finds a file by the given name in one of
    the configuration directories.

    :param roots:
        see `roots` in :py:func:`.configDirectories` for details

    :param user:
        see `user` in :py:func:`.configDirectories` for details

    :param system:
        see `system` in :py:func:`.configDirectories` for details

    :param must_exist:
        see `must_exist` in :py:func:`.configDirectories` for details
    """
    filepaths = []

    for directory in configDirectories(roots=roots, user=user, system=system):
        filepath = join(directory, name)
        if filepath not in filepaths and (not must_exist or isfile(filepath)):
            filepaths.append(filepath)

    # filepaths.reverse()  # needs to be loaded in the opposite order
    return filepaths