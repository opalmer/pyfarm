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


def configDirectories(
        roots=None, user=DEFAULT_USER_PATHS, system=DEFAULT_SYSTEM_PATHS):
    """
    generator which emits a list of paths were we should
    search for configurationfiles

    :param list roots:
        additional root directories to search for preferences in

    :param bool user:
        If True then add user data directories as well

    :param bool system:
        if True then add system data directories as well

    :param bool cull:
        if True then remove directories which do not contain
        preference files
    """
    if roots is None:
        roots = [DEFAULT_CONFIG_ROOT]
    elif isinstance(roots, basestring):
        roots = [roots]

    assert isinstance(__version__, (list, tuple)), \
        "expected list or tuple for version"

    appdirs = AppDirs("pyfarm", "pyfarm")

    # environment paths before go before our paths
    if "PYFARM_ETC" in os.environ:
        roots[:0] = filter(bool, os.environ["PYFARM_ETC"].split(":"))

    # followed by the system
    if system:
        roots.insert(0, os.path.join(appdirs.site_data_dir, DEFAULT_SUBDIR))

    # ... then user paths
    if user:
        roots.insert(0, os.path.join(appdirs.user_data_dir, DEFAULT_SUBDIR))

    # generate a list of subdirectories to visit so
    # preference files can be locked off
    version_subdirs = [""]
    version_parts = []
    for v in imap(str, __version__):
        version_parts.append(v)
        add_x_count = len(__version__) - len(version_parts)
        version_entry = ".".join(version_parts + ['x'] * add_x_count)
        version_subdirs.append(version_entry)

    # explict version should be first
    version_subdirs.reverse()

    for root, versiondir in product(roots, version_subdirs):
        path = os.path.join(root, versiondir) if versiondir else root
        if isdir(path):
            yield path
# end configDirectories


def configFiles(name, user=DEFAULT_USER_PATHS, system=DEFAULT_SYSTEM_PATHS):
    """
    Finds a file by the given name in one of
    the configuration directories.
    """
    searched = []
    filepaths = []

    for directory in configDirectories(user=user, system=system):
        filepath = join(directory, name)
        if isfile(filepath):
            filepaths.append(filepath)
        else:
            searched.append(directory)

    filepaths.reverse()  # needs to be loaded in the opposite order
    return filepaths
# end configFiles
