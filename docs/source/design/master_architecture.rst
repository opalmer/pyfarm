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

.. _design-master_architecture:

Master Architecture
===================
This document defines the term `master` and what it means from a top down
perspective.  It does not cover any of the individual subsystems in depth
however those topics may be the subject of other documents at a later date.


Overview
--------

The master operates as a manager of multiple agents, their tasks, and also
serves as a point of communication between the agent(s) and the database.
A master does not always represent a single machine, more often it represents
a resource that points to several hosts.  This is done to ensure that a request
can be routed to any master that the frontend sees as an appropriate resource.
For smaller groups or single users however the communication protocol however
the setup can still be simplified down to a few step.

In a general sense the master can now scale in one of several ways:

.. csv-table::
    :header: Type, Scalability, Failures Over Time, Description
    :widths: 50, 15, 25, 120

    Single Master, Low, High, Each agent speaks directly with the master
    HTTP Frontend + Multiple Masters, Moderate, Low, Each agent speaks to an HTTP server which distributes the request to the masters
    Load Balancer + Multiple Frontends/Masters, High, Very Low, Each agent speaks to a load balancer which then distributes the request to a frontend/http server combination


Capabilities
------------

Because the master will serve as the single point for each agent to talk
to it must be able to serve multiple requests.  See below for more
information.

| **TODO** add links to json document specifications, url resources, and media types.
| **TODO** add additional (missing) capabilities

Job Processing
##############
    * job state changes
    * get assignment
    * job relationship information
    * query current assignments
    * modify running jobs - (**TODO** needs definition, is this a direct
      communication?  Queue the request to another 'talkback' service?)
    * new job/task creation *(eliminates the need for 3rd party code to require*
      *sqlalchemy, twisted, etc)*


Agent Information
#################
    * agent state changes (**some** assumptions should be made about the
      state to save on queries)
    * acquire new agent limitations (max ram/load/etc) with the agent itself
    * unhandled exceptions raised
    * current resource usage
        * ram
        * cpu load
        * diskspace?
        * io counters?
    * information
        * last communication time (could be inferred)
        * tasks assigned
        * last failures
        * processed jobs


Master Information
##################
    * requests handled
    * unhandled exceptions raised
    * information (tbd)
        * request counter
        * average request times?


General Response Implementation Notes
-------------------------------------

This section will be expanded in a later design however that future design
include some information on:


* **Agent Talkback:** Certain requests to the master may require us to
  communicate something with the client.  More than likely this will be
  a 'talkback' server that simply send commands to agents from the masters
* **Referenced Resources (href):** Responses should reference other resources
  rather than containing all the information requested (perhaps include an
  override in the url?). For example 'processed jobs' for an agent
  would reference other resource urls to retrieve the job object(s).
* **Models:** Received responses should be wrapped into a model object so
  resolution of additional resources or resolution of properties can be
  performed in a uniform fashion.  The *key* here is to keep as few
  dependencies on the underlying data structure and as few dependencies on
  libraries outside of Python itself.
* **Time:**: Datetime should always be represented by ISO 8601 and set in GMT
* **State Information:** Information that is not specific to a single host (
  never needs to be known in other scopes) should not be stored in the session