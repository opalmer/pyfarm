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

from warnings import warn

from datetime import timedelta, datetime
from werkzeug.wrappers import Response
from werkzeug.http import http_date


class expires(object):
    """
    Adds an `Expires` header a response object

    :type name: str
    :param name:
        if provided then retrieve the expiration length
        from a configuration file

    :type now: :py:class:`.datetime`
    :param now:
        defines the time we will calculate the timedelta
        against
    """
    def __init__(self, seconds=0, minutes=0, hours=0,
                 days=0, weeks=0, now=None, name=None):
        # TODO: add handling for named expioration (config based)
        self.now = now
        self.name = name
        self.kwargs = {
            "seconds": seconds, "minutes": minutes,
            "hours": hours, "days": days, "weeks": weeks
        }

        # cannot have keywords if `name` is
        if self.name is not None:
            for name, value in self.kwargs.iteritems():
                if value != 0:
                    msg = "exact expiration cannot be mixed with configured "
                    msg += "expiration"
                    raise ValueError(msg)

    def __call__(self, function):
        def wrapped(*args):
            result = function(*args)

            if isinstance(result, Response):
                now = datetime.now() if self.now is None else self.now
                date = now + timedelta(**self.kwargs)
                result.headers.add_header(
                    "Expiration", http_date(date.timetuple())
                )
            else:
                warn("%s is not a response object" % result)

            return result
        return wrapped
