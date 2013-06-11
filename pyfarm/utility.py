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
import socket
import getpass
import binascii


def user():
    """returns the current user name"""
    try:
        import pwd
        return pwd.getpwuid(os.getuid())[0]

    except ImportError:
        return getpass.getuser()


def randport():
    """returns a port which we are able to bind to"""
    s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    s.bind(("", 0))
    address = s.getsockname()[1]
    s.close()
    return address


def randstr():
    """returns a random hexidecimal string based on :func:`os.urandom`"""
    return binascii.b2a_hex(os.urandom(6))


def randint():
    """returns a base 16 integer from :func:`randstr`"""
    return int(randstr(), 16)