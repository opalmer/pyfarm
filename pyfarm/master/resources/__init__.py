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
Resources which the master processes and returns to agents, clients,
etc via a REST api.  Examples related to requests in this section will
be typically be formatted like below.

    ::

        GET 200 /hosts/1
        {
            "hostname": "foo",
            "address": "123.456.789.100",
            "port": 65535
            [ ..... ]
        }


In the example above `GET` is the request being made, `/hosts/1/` is the
resource requested, and 200 is the expected return code to get the result
documented below.

.. note::
    The url structure does not include the version from the url so documentation
    referring to `/foo` should infer a version number such as `/v1/foo`
"""