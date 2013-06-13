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
Reimplementation of certain objects and functions which are not
present in earlier versions of Python.

.. note::
    this code comes from the standard library, none of it is original
"""

import __builtin__


def product(*args, **kwds):
    """
    >>> list(product('AB', 'cd'))
    [('A', 'c'), ('A', 'd'), ('B', 'c'), ('B', 'd')]

    >>> list(product(range(2), repeat=2))
    [(0, 0), (0, 1), (1, 0), (1, 1)]
    """
    pools = map(tuple, args) * kwds.get('repeat', 1)
    result = [[]]
    for pool in pools:
        result = [x+[y] for x in result for y in pool]
    for prod in result:
        yield tuple(prod)


def permutations(iterable, r=None):
    """
    >>> list(permutations('AB', 2))
    [('A', 'B'), ('B', 'A')]

    >>> list(permutations(range(3)))
    [(0, 1, 2), (0, 2, 1), (1, 0, 2), (1, 2, 0), (2, 0, 1), (2, 1, 0)]
    """
    pool = tuple(iterable)
    n = len(pool)
    r = n if r is None else r
    for indices in product(range(n), repeat=r):
        if len(set(indices)) == r:
            yield tuple(pool[i] for i in indices)


class _property(__builtin__.property):
    """
    Backport of some of Python 2.6's property setup into Python 2.5
    """
    __metaclass__ = type

    def setter(self, method):
        return property(self.fget, method, self.fdel)

    def deleter(self, method):
        return property(self.fget, self.fset, method)