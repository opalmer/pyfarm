#!/bin/bash

client=$HOME/pyfarm/Client.py
main=$HOME/pyfarm/Main.pyw

python py2depgraph.py $client | python depgraph2dot.py | dot -T $1 -o 
output/Client.$1
python py2depgraph.py $main | python depgraph2dot.py | dot -T $1 -o 
output/Main.$1
