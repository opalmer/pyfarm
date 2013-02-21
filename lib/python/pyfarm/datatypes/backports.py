# No shebang line, this module is meant to be imported
#
# This file is part of PyFarm.
# Copyright (C) 2008-2013 Oliver Palmer
#
# PyFarm is free software: you can redistribute it and/or modify
# it under the terms of the GNU Lesser General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# PyFarm is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public License
# along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.

"""
Retrieves the proper object or constructs a new one to match later
versions of Python.  For any missing object the init file in the PyFarm
package will handle retrieval and setup of an object from this module.  As an
example:

>>> try:
...     from itertools import permutations
... except ImportError:
...     import itertools
...     from pyfarm.datatypes.backports import permutations
...     itertools.permutations = permutations
"""

import sys

try:
    from collections import OrderedDict
except ImportError:
    from ordereddict import OrderedDict

try:
    from collections import namedtuple
except ImportError:
    from operator import itemgetter as _itemgetter
    from keyword import iskeyword as _iskeyword
    import sys as _sys

    def namedtuple(typename, field_names, verbose=False, rename=False):
        """
        Returns a new subclass of tuple with named fields.

        >>> Point = namedtuple('Point', 'x y')
        >>> Point.__doc__                   # docstring for the new class
        'Point(x, y)'
        >>> p = Point(11, y=22)             # instantiate with positional args or keywords
        >>> p[0] + p[1]                     # indexable like a plain tuple
        33
        >>> x, y = p                        # unpack like a regular tuple
        >>> x, y
        (11, 22)
        >>> p.x + p.y                       # fields also accessable by name
        33
        >>> d = p._asdict()                 # convert to a dictionary
        >>> d['x']
        11
        >>> Point(**d)                      # convert from a dictionary
        Point(x=11, y=22)
        >>> p._replace(x=100) # _replace() is like str.replace() but targets named fields
        Point(x=100, y=22)
        """
        # Parse and validate the field names.  Validation serves two purposes,
        # generating informative error messages and preventing template injection attacks.
        if isinstance(field_names, basestring):
            field_names = field_names.replace(',', ' ').split() # names separated by whitespace and/or commas
        field_names = tuple(map(str, field_names))
        if rename:
            names = list(field_names)
            seen = set()
            for i, name in enumerate(names):
                if (not min(c.isalnum() or c=='_' for c in name) or _iskeyword(name)
                    or not name or name[0].isdigit() or name.startswith('_')
                    or name in seen):
                    names[i] = '_%d' % i
                seen.add(name)
            field_names = tuple(names)
        for name in (typename,) + field_names:
            if not min(c.isalnum() or c=='_' for c in name):
                raise ValueError('Type names and field names can only contain alphanumeric characters and underscores: %r' % name)
            if _iskeyword(name):
                raise ValueError('Type names and field names cannot be a keyword: %r' % name)
            if name[0].isdigit():
                raise ValueError('Type names and field names cannot start with a number: %r' % name)
        seen_names = set()
        for name in field_names:
            if name.startswith('_') and not rename:
                raise ValueError('Field names cannot start with an underscore: %r' % name)
            if name in seen_names:
                raise ValueError('Encountered duplicate field name: %r' % name)
            seen_names.add(name)

        # Create and fill-in the class template
        numfields = len(field_names)
        argtxt = repr(field_names).replace("'", "")[1:-1]   # tuple repr without parens or quotes
        reprtxt = ', '.join('%s=%%r' % name for name in field_names)
        template = """class %(typename)s(tuple):
            '%(typename)s(%(argtxt)s)' \n
            __slots__ = () \n
            _fields = %(field_names)r \n
            def __new__(_cls, %(argtxt)s):
                return _tuple.__new__(_cls, (%(argtxt)s)) \n
            @classmethod
            def _make(cls, iterable, new=tuple.__new__, len=len):
                'Make a new %(typename)s object from a sequence or iterable'
                result = new(cls, iterable)
                if len(result) != %(numfields)d:
                    raise TypeError('Expected %(numfields)d arguments, got %%d' %% len(result))
                return result \n
            def __repr__(self):
                return '%(typename)s(%(reprtxt)s)' %% self \n
            def _asdict(self):
                'Return a new dict which maps field names to their values'
                return dict(zip(self._fields, self)) \n
            def _replace(_self, **kwds):
                'Return a new %(typename)s object replacing specified fields with new values'
                result = _self._make(map(kwds.pop, %(field_names)r, _self))
                if kwds:
                    raise ValueError('Got unexpected field names: %%r' %% kwds.keys())
                return result \n
            def __getnewargs__(self):
                return tuple(self) \n\n""" % locals()
        for i, name in enumerate(field_names):
            template += '        %s = _property(_itemgetter(%d))\n' % (name, i)
        if verbose:
            print template

        # Execute the template string in a temporary namespace
        namespace = dict(_itemgetter=_itemgetter, __name__='namedtuple_%s' % typename,
                         _property=property, _tuple=tuple)
        try:
            exec template in namespace
        except SyntaxError, e:
            raise SyntaxError(e.message + ':\n' + template)
        result = namespace[typename]

        # For pickling to work, the __module__ variable needs to be set to the frame
        # where the named tuple is created.  Bypass this step in enviroments where
        # sys._getframe is not defined (Jython for example) or sys._getframe is not
        # defined for arguments greater than 0 (IronPython).
        try:
            result.__module__ = _sys._getframe(1).f_globals.get('__name__', '__main__')
        except (AttributeError, ValueError):
            pass

        return result
    # end namedtuple

try:
    from itertools import product
except ImportError:
    def product(*args, **kwds):
        """
        product(*iterables) --> product object

        Cartesian product of input iterables.  Equivalent to nested for-loops.

        For example, product(A, B) returns the same as:  ((x,y) for x in A for y in B).
        The leftmost iterators are in the outermost for-loop, so the output tuples
        cycle in a manner similar to an odometer (with the rightmost element changing
        on every iteration).

        product('ab', range(3)) --> ('a',0) ('a',1) ('a',2) ('b',0) ('b',1) ('b',2)
        product((0,1), (0,1), (0,1)) --> (0,0,0) (0,0,1) (0,1,0) (0,1,1) (1,0,0) ...
        """
        # product('ABCD', 'xy') --> Ax Ay Bx By Cx Cy Dx Dy
        # product(range(2), repeat=3) --> 000 001 010 011 100 101 110 111
        pools = map(tuple, args) * kwds.get('repeat', 1)
        result = [[]]
        for pool in pools:
            result = [x+[y] for x in result for y in pool]
        for prod in result:
            yield tuple(prod)
    # end product

try:
    from itertools import permutations
except ImportError:
    def permutations(iterable, r=None):
        """
        permutations(iterable[, r]) --> permutations object

        Return successive r-length permutations of elements in the iterable.

        permutations(range(3), 2) --> (0,1), (0,2), (1,0), (1,2), (2,0), (2,1)
        """
        pool = tuple(iterable)
        n = len(pool)
        r = n if r is None else r
        for indices in product(range(n), repeat=r):
            if len(set(indices)) == r:
                yield tuple(pool[i] for i in indices)
    # end permutations

if sys.version_info[0:2] <= (2, 6):
    import os as _os
    from tempfile import (
        template, gettempdir, _bin_openflags, _text_openflags,
        _mkstemp_inner,
    )

    class _TemporaryFileWrapper:
        """Temporary file wrapper

        This class provides a wrapper around files opened for
        temporary use.  In particular, it seeks to automatically
        remove the file when it is no longer needed.
        """

        def __init__(self, file, name, delete=True):
            self.file = file
            self.name = name
            self.close_called = False
            self.delete = delete
        # end __init__

        def __getattr__(self, name):
            # Attribute lookups are delegated to the underlying file
            # and cached for non-numeric results
            # (i.e. methods are cached, closed and friends are not)
            file = self.__dict__['file']
            a = getattr(file, name)
            if not issubclass(type(a), type(0)):
                setattr(self, name, a)
            return a
        # end __getattr__

        # The underlying __enter__ method returns the wrong object
        # (self.file) so override it to return the wrapper
        def __enter__(self):
            self.file.__enter__()
            return self
        # end __enter__

        # NT provides delete-on-close as a primitive, so we don't need
        # the wrapper to do anything special.  We still use it so that
        # file.name is useful (i.e. not "(fdopen)") with NamedTemporaryFile.
        if _os.name != 'nt':
            # Cache the unlinker so we don't get spurious errors at
            # shutdown when the module-level "os" is None'd out.  Note
            # that this must be referenced as self.unlink, because the
            # name TemporaryFileWrapper may also get None'd out before
            # __del__ is called.
            unlink = _os.unlink

            def close(self):
                if not self.close_called:
                    self.close_called = True
                    self.file.close()
                    if self.delete:
                        self.unlink(self.name)

            # end close

            def __del__(self):
                self.close()
            # end __del__

            # Need to trap __exit__ as well to ensure the file gets
            # deleted when used in a with statement
            def __exit__(self, exc, value, tb):
                result = self.file.__exit__(exc, value, tb)
                self.close()
                return result
            # end __exit__
    # end _TemporaryFileWrapper

    def NamedTemporaryFile(mode='w+b', bufsize=-1, suffix="",
                           prefix=template, dir=None, delete=True):
        """Create and return a temporary file.
        Arguments:
        'prefix', 'suffix', 'dir' -- as for mkstemp.
        'mode' -- the mode argument to os.fdopen (default "w+b").
        'bufsize' -- the buffer size argument to os.fdopen (default -1).
        'delete' -- whether the file is deleted on close (default True).
        The file is created as mkstemp() would do it.

        Returns an object with a file-like interface; the name of the file
        is accessible as file.name.  The file will be automatically deleted
        when it is closed unless the 'delete' argument is set to False.
        """

        if dir is None:
            dir = gettempdir()

        if 'b' in mode:
            flags = _bin_openflags
        else:
            flags = _text_openflags

        # Setting O_TEMPORARY in the flags causes the OS to delete
        # the file when it is closed.  This is only supported by Windows.
        if _os.name == 'nt' and delete:
            flags |= _os.O_TEMPORARY

        (fd, name) = _mkstemp_inner(dir, prefix, suffix, flags)
        file = _os.fdopen(fd, mode, bufsize)
        return _TemporaryFileWrapper(file, name, delete)
