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

"""module for dealing with storage and retrieval of the master"""

import socket

#from pyfarm.db import query # TODO: replace with new objects
from pyfarm.logger import Logger
from pyfarm.datatypes.network import FQDN
from pyfarm import errors

logger = Logger(__name__)

def get(master=None):
    """
    :param string master:
        if provided then use this value as the master regardless of what
        the master is in the database or what the hostname is
    """
    if master is None:
        try:
            logger.debug("retrieving master from database")
            master = query.master.get(FQDN)

        except errors.HostNotFound:
            pass

    if master is None:
        master = query.master.online()

    # make sure that the resulting master value
    # is valid
    if master is None:
        raise ValueError(
            "expected master to be a non-null value please check your input arguments to pyfarm_client"
        )

    # if we cannot resolve the provided master then we will probably
    # have trouble connecting so just fail here
    try:
        logger.debug("ensuring we can resolve %s's address" % master)
        address = socket.gethostbyname(master)

    except socket.gaierror:
        logger.error("master '%s' failed to resolve to a valid address" % master)
        raise

    return master
# end get
