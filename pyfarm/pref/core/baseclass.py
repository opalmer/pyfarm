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
main preferences module which contains a base class for use outside of
:mod:`pyfarm.preferences`
"""

from pyfarm.pref.core.loader import Loader
from pyfarm.pref.core.enums import NOTSET

class Preferences(object):
    """
    The main preferences object which may be subclassed by other
    preference objects for extension purposes.

    :param string prefix:
        the directory prefix to use when searching for
        preferences

    :param string filename:
        the filename to use for queries.  As an example these
        will have the same results:

            >>> p1 = Preferences()
            >>> p2 = Preferences(filename='database')
            >>> assert p1.get('database.setup') == p2.get('setup')
    """
    _data = {}

    def __init__(self, prefix=None, filename=None):
        self.prefix = '' if prefix is None else prefix
        self.filename = filename
    # end __init__

    @classmethod
    def _get(
            cls, key, failobj=NOTSET, force=False, return_loader=False,
            filename=None
    ):
        """
        See :meth:`get` for this classmethod's documentation.  This
        classmethod is called internally by :meth:`get` and returns class
        level data
        """
        # if a string key is present, a string filename is present
        # and the key happens to start with the database name then
        # replace the beginning of the key with "" to ensure both
        # a.b.c and b.c will work if filename is 'a'
        if filename is not None:
            startswith_filename = all([
                isinstance(key, basestring),
                isinstance(filename, basestring),
                key.startswith(filename + ".")
            ])

            if startswith_filename:
                key = key.replace(filename + ".", "")

        # before we do anything check to see if the requested key
        # is something that maps to a callable function
        split = key.split(".")
        if filename is None:
            filename = split[0]
            key_uri = ".".join(split[1:])
        else:
            key_uri = ".".join(split)

        # load the underlying data if necessary, retrieve it from
        # cace otherwise
        if filename not in cls._data:
            data = cls._data[filename] = Loader(filename, force=force)
        else:
            data = cls._data[filename]

        # simply return the data if the filename
        # we found was the same as the key key
        if filename != key:
            try:
                data = data[key_uri]

            except KeyError:
                if failobj is not NOTSET:
                    return failobj
                raise
        else:
            return data if return_loader else data.data.copy()

        return data
    # end _get

    def get(self,
            key, failobj=NOTSET, force=False, return_loader=False,
            **kwargs
        ):
        """
        Base classmetod which is used for the sole purpose of data
        retrieval from the yaml file(s).

        :param failobj:
           the object to return in the even of failure, if this value is
           not provided the original exception will be raised

        :param boolean force:
           if True then force reload the underlying file(s)

        :param boolean return_loader:
           if True and the key requested happened to be a preference file
           name then return the loader instead of a copy of the loader data

        :param kwargs:
            any additional keywords to pass along

        :exception KeyError:
           This behaves slightly differently from :meth:`dict.get` in that
           unless failobj is set it will reraise the original exception
        """
        result = self._get(
            key, failobj=failobj, force=force, return_loader=return_loader,
            filename=kwargs.get('filename', self.filename)
        )

        if result is NOTSET:
            raise KeyError("key %s does not exist in data" % key)
        else:
            return result
    # end get
# end Preferences
