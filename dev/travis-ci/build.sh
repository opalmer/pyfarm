#!/bin/bash

. dev/travis-ci/functions.sh

title Python Packages
pip freeze

title Building
runcmd make -C docs html
runcmd nosetests tests
todo write out/copy test database configuration file