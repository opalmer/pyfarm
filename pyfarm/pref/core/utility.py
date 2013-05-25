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

import os
from itertools import imap
from fnmatch import filter as fnfilter
from appdirs import AppDirs

try:
    from itertools import product
except ImportError:
    from pyfarm.datatypes.backports import product


def configurationDirs(
        version, roots, subdir="etc", extension="*.yml",
        user=True, system=True, cull=True
    ):
    """
    returns a list of preference files which exist on disk

    :param list roots:
        additional root directories to search for preferences in

    :param string subdir:
        the subdirectory under each root that we're looking for

    :param string extension:
        the file extension we're looking for

    :param bool user:
        If True then add user data directories as well

    :param bool system:
        if True then add system data directories as well

    :param bool cull:
        if True then remove directories which do not contain
        preference files
    """
    if roots is None:
        roots = []
    elif isinstance(roots, basestring):
        roots = [roots]

    assert isinstance(version, (list, tuple)), \
        "expected list or tuple for version"

    appdirs = AppDirs("pyfarm", "pyfarm")

    if user:
        roots.insert(0, os.path.join(appdirs.user_data_dir, subdir))

    if system:
        roots.insert(1, os.path.join(appdirs.site_data_dir, subdir))

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

    directories = []
    for root, versiondir in product(roots, version_subdirs):
        path = os.path.join(root, versiondir) if versiondir else root
        if (
            not cull
            or (os.path.isdir(path) and fnfilter(os.listdir(path), extension))
        ):
            directories.append(path)

    return directories
# end configurationDirs