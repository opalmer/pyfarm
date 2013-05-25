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

BOLD=`tput bold`
GREEN=`tput setf 2`
YELLOW=`tput setf 3`
RED=`tput setf 4`
NORMAL=`tput sgr 0`

function title {
    echo "========================================================="
    echo "$1 $2"
    echo "========================================================="
}

function runcmd {
    echo "${BOLD}running: $@${NORMAL}"
    $@
    exit_code=$?

    if [ $exit_code -ne 0 ]; then
        echo "${BOLD}${RED}failed: $@ (exit $exit_code)${NORMAL}"
        exit $exit_code
    else
        echo "${BOLD}${GREEN}finished: $@${NORMAL}"
    fi
}

function todo {
    echo "${BOLD}${YELLOW}TODO: $1${NORMAL}"
}