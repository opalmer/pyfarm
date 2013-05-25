#!/bin/bash

. dev/travis-ci/functions.sh

title Python Packages
pip freeze

if [ "BUILD_DOCS" == "true" ]; then
    title Build Step: documentation
    runcmd make -C docs html
fi

title Build Step: tests
runcmd nosetests tests
todo write out/copy test database configuration file