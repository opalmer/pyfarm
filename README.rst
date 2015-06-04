PyFarm
======

**NOTE**: This repo has been broken down into individual projects under
https://github.com/pyfarm for  better transparency, isolation, and tracking.
All that remains in this repo are tools for development and deployment.

A Python based distributed job system which is intended to be easy to deploy
and maintain.  Initially developed to be used solely by the Visual Effects
industry there is the intention to expand into other areas as well such as the
general scientific community.  Currently the project is undergoing heavy
development and this page serves as a placeholder.  For more direct information
about the project please use the resources below or view the online
`documentation <https://pyfarm.readthedocs.org>`_


Subprojects
===========
Below are the current subprojects of PyFarm which are under active development.
More detailed information about the individual project(s) can be found in either
the documentation or in the respective repo.

* `pyfarm.core <https://github.com/pyfarm/pyfarm-core>`_

    Sub-library containing core modules, classes, and data types which are
    used throughout the project.

    .. image:: https://travis-ci.org/pyfarm/pyfarm-core.png?branch=master
        :target: https://travis-ci.org/pyfarm/pyfarm-core
        :align: left

    .. image:: https://coveralls.io/repos/pyfarm/pyfarm-core/badge.png?branch=master
        :target: https://coveralls.io/r/pyfarm/pyfarm-core?branch=master
        :align: left

* `pyfarm.master <https://github.com/pyfarm/pyfarm-master>`_

    Sub-library which contains the code necessary to communicate with the
    database via a REST api.

    .. image:: https://travis-ci.org/pyfarm/pyfarm-master.png?branch=master
        :target: https://travis-ci.org/pyfarm/pyfarm-master
        :align: left

    .. image:: https://coveralls.io/repos/pyfarm/pyfarm-master/badge.png?branch=master
        :target: https://coveralls.io/r/pyfarm/pyfarm-master?branch=master
        :align: left

* `pyfarm.agent <https://github.com/pyfarm/pyfarm-agent>`_

    Core module containing code to run PyFarm's agent.

    .. image:: https://travis-ci.org/pyfarm/pyfarm-agent.png?branch=master
        :target: https://travis-ci.org/pyfarm/pyfarm-agent
        :align: left

    .. image:: https://coveralls.io/repos/pyfarm/pyfarm-agent/badge.png?branch=master
        :target: https://coveralls.io/r/pyfarm/pyfarm-agent?branch=master
        :align: left
