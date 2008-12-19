#!/usr/bin/python
import sys
from subprocess import Popen,PIPE

p = Popen(['ping', '-c 4','google.com'],stdout=PIPE)

while True:
    line = p.stdout.readline()
    print line.split('\n')[0]
    if line == '' and p.poll() != None: break
    # the 'o' variable stores a line from the command's stdout
    # do anything u wish with the 'o' variable here
    # this loop will break once theres a blank output
    # from stdout and the subprocess have ended