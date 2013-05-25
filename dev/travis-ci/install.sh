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

PYTHON_PACKAGES="distribute==0.6.42"
SYSTEM_PACKAGES="libyaml-dev"

if [ "$DB" == "postgres" ]; then
    SYSTEM_PACKAGES="$SYSTEM_PACKAGES libpq-dev"
    PYTHON_PACKAGES="$PYTHON_PACKAGES psycopg2"

elif [ "$DB" == "mysql" ]; then
    PYTHON_PACKAGES="$PYTHON_PACKAGES pymysql"
fi

title Installing Packages
retrycmd sudo apt-get -y install $SYSTEM_PACKAGES
retrycmd pip install $PYTHON_PACKAGES --use-mirrors
retrycmd pip install -e . --use-mirrors