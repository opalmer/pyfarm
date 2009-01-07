#!/bin/bash
# PURPOSE: to connect to and run the latest client on the remote machines

hosts=("master" "render01" "render02" "render03")

# connect to each host and run the correct command
for host in ${hosts[*]}
do
	xterm -e "ssh render@$host '/farm/projects/PyFarm/testing/proto1.5/findClients_client'"&
done
