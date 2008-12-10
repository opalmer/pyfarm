#!/usr/bin/python
'''
AUTHOR: Oliver Palmer
CONTACT: opalme20@student.scad.edu || (703)725-6544
INITIAL: Dec 9 2008
PURPOSE: This program is used for testing purposes ONLY, not to publish
'''
import os
from lib.Install import SystemPackages

pkg = SystemPackages('/home/opalmer/build/network/settings/packages', 'nfs', 'client')
pkg.install()