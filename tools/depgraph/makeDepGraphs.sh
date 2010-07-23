#!/bin/bash

client=$HOME/pyfarm/Client.py
main=$HOME/pyfarm/Main.pyw

python py2depgraph.py $client | python depgraph2dot.py | dot -T jpg -o output/Client.jpg
python py2depgraph.py $main | python depgraph2dot.py | dot -T jpg -o output/Main.jpg