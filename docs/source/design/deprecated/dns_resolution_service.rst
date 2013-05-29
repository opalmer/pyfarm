.. Copyright 2013 Oliver Palmer
..
.. Licensed under the Apache License, Version 2.0 (the "License");
.. you may not use this file except in compliance with the License.
.. You may obtain a copy of the License at
..
..   http://www.apache.org/licenses/LICENSE-2.0
..
.. Unless required by applicable law or agreed to in writing, software
.. distributed under the License is distributed on an "AS IS" BASIS,
.. WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
.. See the License for the specific language governing permissions and
.. limitations under the License.

.. _design-dns_resource_resolution:

Domain Name Services for Resource Resolution
============================================

.. note::
    This design has been **deprecated** due to the design of the
    :ref:`master <design-master_architecture>` which should remove the need
    for this system


In the past pyfarm has always made the assumption that a single DNS name
was equal to a single resource or address.  Primarily this was done for
simplicity however for services which need to scale, a single name can usually
be resolved to multiple address for load distribution and redundancy:

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
approach so agents don't have to talk to an SQL server for each request.

Server Implementation
---------------------
In short, pyfarm will implement a DNS server with custom responses for certain
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
agents and five masters you only want to inform agents about masters that:

  * are currently 'enabled' in the database, configured, etc.
  * have not had a large number of failures recently (server busy response is
    not a failure but should bear some weight on the decision process)

From a DNS client perspective this should help to ensure that the address
returned for a given DNS query has a higher chance of a successful connection
which will limited the number of overall exceptions handled.

REST APIs
+++++++++
Since the DNS server will act as a middleman of sorts between DNS queries, the
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
pyfarm's DNS server will be configurable to respect either TTL or operate on
a fixed timer.  Although this does not follow the usual design of DNS it will
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
database queries, and more control over agent <-> master/group communication.
In addition companies or individuals with the desire and expertise could use
their own DNS infrastructure for master or group IP address resolution.
