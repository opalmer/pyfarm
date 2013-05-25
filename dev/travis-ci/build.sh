#!/bin/bash
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

. dev/travis-ci/functions.sh

title Python Packages
pip freeze

if [ "$BUILD_DOCS" == "true" ]; then
    title Build Step: documentation
    runcmd make -C docs html
fi

title Build Step: tests

# coveralls does not support 2.5...
if [ "$TRAVIS_PYTHON_VERSION" != "2.5" ]; then
    runcmd coverage run --source=pyfarm tests/* --verbose -s
else
    runcmd nosetests --verbose -s
fi

todo write out/copy test database configuration file