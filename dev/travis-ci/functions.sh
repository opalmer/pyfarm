#!/bin/bash

BOLD=`tput bold`
GREEN=`tput setf 2`
YELLOW=`tupt setf 3`
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