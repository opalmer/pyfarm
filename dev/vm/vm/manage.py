# No shebang line, this module is meant to be imported
#
# This file is part of PyFarm.
# Copyright (C) 2008-2012 Oliver Palmer
#
# PyFarm is free software: you can redistribute it and/or modify
# it under the terms of the GNU Lesser General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# PyFarm is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public License
# along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.

import time
import types

import info
import process

def __names(names):
    '''returns a list even if provided a string'''
    if isinstance(names, types.StringTypes):
        return [names]
    return names
# end __names

def shutdown(names):
    '''shutdown the vm or vms by name'''
    names = __names(names)

    print "attempting to shutdown %s:" % names
    vmlist = info.list()
    longest_name = max([len(name) for name in names])

    for name in names:
        space = " "*(longest_name - len(name))
        if name not in vmlist:
            print "%s%s - no such vm" % (name, space)
            continue

        if not info.running(name):
            print "%s%s - not running" % (name, space)
            continue

        failed = process.run('controlvm', name, 'acpipowerbutton')
        time.sleep(1)
        if failed:
            print "%s%s - shutdown failed" % (name, space)
        else:
            print "%s%s - shutdown" % (name, space)
# end shutdown

def start(names):
    '''start the vm or vms by name'''
    names = __names(names)

    print "attempting to start %s:" % names
    vmlist = info.list()
    longest_name = max([len(name) for name in names])

    for name in names:
        space = " "*(longest_name - len(name))
        if name not in vmlist:
            print "%s%s - no such vm" % (name, space)
            continue

        if info.running(name):
            print "%s%s - running" % (name, space)
            continue

        failed = process.run('startvm', name, '--type', 'headless')
        time.sleep(1)
        if info.running(name):
            print "%s%s - started" % (name, space)
        else:
            print "%s%s - start failed" % (name, space)
# end start

def reset(names):
    '''reset the vm or vms by name'''
    names = __names(names)

    print "attempting to reset %s:" % names
    vmlist = info.list()
    longest_name = max([len(name) for name in names])

    for name in names:
        space = " "*(longest_name - len(name))
        if name not in vmlist:
            print "%s%s - no such vm" % (name, space)
            continue

        if not info.running(name):
            print "%s%s - not running" % (name, space)
            continue

        failed = process.run('controlvm', name, 'reset')
        time.sleep(1)
        if not failed:
            print "%s%s - reset" % (name, space)
        else:
            print "%s%s - reset failed" % (name, space)
# end reset