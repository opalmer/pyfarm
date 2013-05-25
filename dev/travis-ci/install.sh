#!/bin/bash

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
runcmd sudo apt-get -y install $SYSTEM_PACKAGES
runcmd pip install $PYTHON_PACKAGES
runcmd pip install -e . --use-mirrors