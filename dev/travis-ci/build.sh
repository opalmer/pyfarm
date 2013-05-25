#!/bin/bash

. dev/travis-ci/functions.sh

title Python Packages
pip freeze

title Build Step: documentation
runcmd make -C docs html
title Build Step: tests
runcmd nosetests tests
todo write out/copy test database configuration file