#!/bin/bash

. dev/travis-ci/functions.sh

title Python Packages
pip freeze

if [ "BUILD_DOCS" == "true" ]; then
    title Build Step: documentation
    runcmd make -C docs html
fi

title Build Step: tests

# coveralls does not support 2.5...
if [ "TRAVIS_PYTHON_VERSION" != "2.5" ]; then
    runcmd coverage run --source=pyfarm tests/* --verbose -s
else
    runcmd nosetests --verbose -s
fi

todo write out/copy test database configuration file