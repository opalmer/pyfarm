#!/bin/bash

echo "========================================================="
echo "Starting ${BASH_SOURCE[0]}"
echo "========================================================="

make -C docs html
pylint lib/python/pyfarm


echo "========================================================="
echo "Finished ${BASH_SOURCE[0]}"
echo "========================================================="