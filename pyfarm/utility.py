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
from decimal import Decimal, ROUND_HALF_DOWN

import IPy

try:
    _range = xrange
except NameError:
    _range = range

PUBLIC_TYPE_NAMES = set(["PRIVATE", "PUBLIC"])
LOCAL_RANGES = set([IPy.IP("169.254.0.0/16"), IPy.IP("127.0.0.0/8")])

def randstr():
    """returns a random hexidecimal string based on :func:`os.urandom`"""
    return binascii.b2a_hex(os.urandom(6))


def randint():
    """returns a base 16 integer from :func:`randstr`"""
    return int(randstr(), 16)


def rounded(value, places=4, rounding=ROUND_HALF_DOWN):
    """
    Returns a floating point number rounded to `places`.

    :type value: float
    :param value:
        the value to round

    :type places: int
    :param places:
        the number of decimal places to round to
    """
    if isinstance(value, int) or int(value) == value:
        return value

    if not isinstance(places, int):
        raise TypeError("expected an integer for `places`")

    if not places >= 1:
        raise ValueError("expected at least one decimal place for `places`")

    # rounding
    dec = Decimal(str(value))
    zeros = "0" * (places - 1)
    rounded_float = dec.quantize(Decimal("0.%s1" % zeros),
                                 rounding=rounding)

    return float(rounded_float)


def isLocalIPv4Address(address):
    """
    Returns True if `addr` is a local network address
    """
    try:
        address = IPy.IP(address)
    except ValueError:
        return False
    else:
        return all([
            address.iptype() in PUBLIC_TYPE_NAMES,
            all([address not in localrange for localrange in LOCAL_RANGES]),
            address != IPy.IP("0.0.0.0")
        ])


def _floatrange_generator(start, end, by, add_endpoint):
    """
    Underlying function for generating float ranges.  Values
    are passed into this function via :func:`floatrange`
    """
    # we can handle either integers or floats here
    float_start = isinstance(start, (float, int))
    float_end = isinstance(end, (float, int))
    float_by = isinstance(by, (float, int))
    last_value = None

    if float_start and end is None and by is None:
        end = start
        by = 1
        i = 0
        while i <= end:
            yield i
            last_value = i
            i = rounded(i + by)

    elif float_start and float_by and end is None:
        end = start
        i = 0
        while i <= end:
            yield i
            last_value = i
            i = rounded(i + by)

    elif float_start and float_end and by is None:
        by = 1
        i = start
        while i <= end:
            yield i
            last_value = i
            i = rounded(i + by)

    elif float_start and float_end and float_by:
        i = start
        while i <= end:
            yield i
            last_value = i
            i = rounded(i + by)

    # produce the endpoint if requested
    if add_endpoint and last_value is not None and last_value != end:
        yield end


def floatrange(start, end=None, by=None, add_endpoint=False):
    """
    Creates a generator which produces a list between `start` and `end` with
    a spacing of `by`.  See below for some examples:

    >>> list(floatrange(2))
    [0, 1]
    >>> list(floatrange(2.5))
    [0, 1, 2]
    >>> list(floatrange(1, by=.15))
    [0, 0.15, 0.3, 0.45, 0.6, 0.75, 0.9]
    >>> list(floatrange(1, by=.15, add_endpoint=True))
    [0, 0.15, 0.3, 0.45, 0.6, 0.75, 0.9, 1]

    :type start: int or float
    :param start:
        the number to start the range at

    :type end: int or float
    :param end:
        the number to finish the range at

    :type by: int or float
    :param by:
        the 'step' to use in the range

    :type add_endpoint: bool
    :param add_endpoint:
        If True then ensure that the last value generated
        by :func:`floatrange` is the end value itself
    """
    if end is not None and end < start:
        raise ValueError("`end` must be greater than `start`")

    if by is not None and by <= 0:
        raise ValueError("`by` must be non-zero")

    int_start = isinstance(start, int)
    int_end = isinstance(end, int)
    int_by = isinstance(by, int)

    # integers - only start/by were provided
    if int_start and end is None and int_by:
        end = start
        start = 0
        return _range(start, end, by)

    # integers - only start was provided
    elif int_start and end is None and by is None:
        return _range(start)

    # integers - start/end/by are all integers
    elif all([int_start, int_end, int_by]):
        return _range(start, end, by)

    else:
        return _floatrange_generator(start, end, by, add_endpoint)


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