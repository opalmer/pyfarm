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

import process

# general helper functions
getHostname = lambda line: line.split(" ")[0].replace('"',"")

def list():
    '''returns a list of all vms on the system'''
    output = []

    for line in process.run('list', 'vms'):
        hostname = getHostname(line)
        output.append(hostname)

    return output
# end list

def running(name=None):
    '''returns a list of currently running virtual machines'''
    output = []
    for line in process.run('list', 'runningvms'):
        hostname = getHostname(line)
        output.append(hostname)

    if name:
        return name in output

    return output
# end running

if __name__ == '__main__':
    print running()