Requirements
============

This document covers the basic requirements for installation and operation of
PyFarm.  These are the requirements to run PyFarm itself regardless of the
service being executed.  These requirements do **not** cover the software
PyFarm may be executing or the infrastructure required.

Summary
-------

* **Python** Python 2.5 through 2.7 is currently supported.  Python 3.0 support
  is planned but cannot be provided until the underlying libraries support
  Python 3.0 as well.

* **Operation System** Linux, Mac, and Windows.  Some features may be limited
  on disabled on certain platforms.

* **Memory** 64MB of memory, more may be required to run some components

* **Storage** 128MB of disk space

Database
--------

PyFarm stores a large amount of the information it needs to operate in a
relational database.  Cross database support is provided by
`SQLAlchemy <http://www.sqlalchemy.org/>`_, for more information on
supported databases see
`this document <http://docs.sqlalchemy.org/en/rel_0_8/dialects/index.html>`_.

Python
------
Below are the libraries generated from
:download:`requirements.txt <../../requirements.txt>` which were used to build
PyFarm.  Some of the below dependencies are only be required for development.

.. include:: include/python_requirements.rst

Supported Software (Job Types)
------------------------------

| **TODO**: add links to job type specific documentation
PyFarm 1.0.0 provides several job types out of the box.  Each of these software
packages will have their own requirements as well so please visit the
manufacturers website for more information.

* Maya
* Houdini
* Nuke

If you wish to request a new builtin type or check the integration status of
one checkout their tickets
`on github <https://github.com/opalmer/pyfarm/issues?labels=3rd+party+integration&page=1&state=open>`_