.. This file is part of PyFarm.
.. Copyright (C) 2008-2013 Oliver Palmer
..
.. PyFarm is free software: you can redistribute it and/or modify
.. it under the terms of the GNU Lesser General Public License as published by
.. the Free Software Foundation, either version 3 of the License, or
.. at your option, any later version.
..
.. PyFarm is distributed in the hope that it will be useful,
.. but WITHOUT ANY WARRANTY; without even the implied warranty of
.. MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
.. GNU Lesser General Public License for more details.
..
.. You should have received a copy of the GNU Lesser General Public License
.. along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.

.. _design-dns_resource_resolution:

Domain Name Services for Resource Resolution
============================================
In the past pyfarm has always made the assumption that a single dns name
was equal to a single resource or address.  In the case of large websites which
have to scale however a single name can usually be resolved to multiple
address to distribute load and reduce the possibility of complete failure:

::

    -> dig google.com

    [ ... ]
    ;; ANSWER SECTION:
    google.com.		192	IN	A	173.194.43.8
    google.com.		192	IN	A	173.194.43.14
    google.com.		192	IN	A	173.194.43.1
    google.com.		192	IN	A	173.194.43.0
    google.com.		192	IN	A	173.194.43.5
    google.com.		192	IN	A	173.194.43.9
    google.com.		192	IN	A	173.194.43.3
    [ ... ]


Rather than reinvent the wheel pyfarm should take advantage of a similar
approach so agents don't have to talk to an SQL server for either random
master or host from a group.

Server Implementation
---------------------
In short, pyfarm will implement a dns server with custom responses for certain
queries.  Any request that we don't have specific handling for will
automatically use the default nameserver(s) to return a response.  On the
surface it will behave just as a normal DNS server would with the exception that
it can handle specific DNS information related to pyfarm.

As an example of this special information, an agent needs to request a new
job from a master but without knowing anything about the master pool:
  * ask DNS service for address of `pyfarm-master` (name would be configurable)
  * follow the :ref:`assignment behaviors design <design-client-server_fallback_behaviors>`
    and either retrieve a job to process or ask DNS for another address to try

In addition to providing agents with a master to connect to this could also
be used for providing a DNS name for general groups of machines.  For example
if several hosts are part of a group processing the same kind of job the DNS
name would be used to resolve an address to connect to and retrieve data from.

Host Filtering
++++++++++++++
We don't always want to return all hosts, in fact we should generally only
return what we consider to be valid hosts.  As an example if you have 500
agents and five masters you only want to inform agents about master that:
  * are currently 'enabled' in the database, configured, etc.
  * have not had a large number of failures recently (server busy response is
    not a failure but should bear some weight on the decision process)

From a dns client perspective this should help to ensure that the address
returned for a given dns query has a higher chance of a successful connection
which will limited the number of overall exceptions handled.

REST APIs
+++++++++
Since the DNS server will act as a middleman of sorts between dns queries, the
SQL database, and the job system we should be able to directly modify its
behavior.  Modifications we should be able to perform on a host or hosts include
TTL updates, force cache refreshing, update(s) to failure counter(s), and
removal of specific hosts from results.  Any changes, such as additions to
failure counters, which should be shared with other possible DNS server
instances should be placed into the database upon receipt instead of waiting
for the next refresh.

Refreshing Data
+++++++++++++++
Unlike DNS which usually respects `TTL <https://en.wikipedia.org/wiki/Time_to_live>`_
pyfarm's dns server will be configurable to respect either TTL or operate on
a fixed timer.  Although this does not following the usual design of DNS it will
ensure the agent systems won't have much of a delay getting the latest results.
This could pose issues for anything respecting TTL however so TTL responses
should be modified for hosts that we are handling to match the timer value if
a timer is being used.

In terms of what kind of data should be refreshed:
  * **enabled state**: If disabled in the database stop processing and remove
    the host from future results.  The enabled state should also be calculated
    based on the number of failures, anything over a certain configurable amount
    should disable the host too.
  * **address**: Information related to the host's network address (ip,
    subnet, FQDN, etc.)


Improvements Over Previous Design
---------------------------------
Compared to the older method where each agent must be assigned a specific master
this implementation will allow for a more scalable infrastructure, fewer
database queries, and more control over agent <-> communication.  In addition
companies or individuals with the desire and expertise could use their own
DNS infrastructure for master or group IP address resolution.
