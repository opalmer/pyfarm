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

from __future__ import division

import os
import binascii


def randstr():
    """returns a random hexidecimal string based on :func:`os.urandom`"""
    return binascii.b2a_hex(os.urandom(6))


def randint():
    """returns a base 16 integer from :func:`randstr`"""
    return int(randstr(), 16)


def floatrange(start, end, by=1, cutoff=True, create_endpoint=True):
    """
    Produces an array which can contain floats.  Results are
    produced by :func:`numpy.arange`

    :type start: int or float
    :param start:
        the number to start the range at

    :type end: int or float
    :param end:
        the number to finish the range at

    :type by: int or float
    :param by:
        the 'step' to use in the range

    :type cutoff: bool
    :param cutoff:
        If True then don't produce numbers that exceed `end`

    :type create_endpoint: bool
    :param create_endpoint:
        if True then always emit `end` as the last number in the range

    # Sample Usage:
    #
    #     >>> list(floatrange(1, 5))
    #     [1, 2, 3, 4, 5]
    #
    #     >>> list(floatrange(1, 5, .5, cutoff=False, create_endpoint=False))
    #     [1.0, 1.5, 2.0, 2.5, 3.0, 3.5, 4.0, 4.5, 5.0, 5.5]
    #
    #     >>> list(floatrange(1, 5, .75, cutoff=True, create_endpoint=True))
    #     [1.0, 1.75, 2.5, 3.25, 4.0, 4.75, 5.0]
    """
    if end < start:
        raise ValueError("`end` must be greater than `start`")

    if by <= 0:
        raise ValueError("`by` must be non-zero")

    raise NotImplementedError("need to remove numpy dependency")


class convert(object):
    """Namespace containing various static methods for conversion"""

    @staticmethod
    def bytetomb(value):
        """
        Convert bytes to megabytes

        >>> convert.bytetomb(10485760)
        10.0
        """
        return value / 1024 / 1024

    @staticmethod
    def mbtogb(value):
        """
        Convert megabytes to gigabytes

        >>> convert.mbtogb(2048)
        2.0
        """
        return value / 1024