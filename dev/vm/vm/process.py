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

import os
import subprocess

def run(*args, **kwargs):
    '''
    runs a a command and waits for it to complete, returning
    data from stdout when complete

    :param list args:
        full argument list for commmand to rin
    '''
    command = kwargs.get('command', 'VBoxManage')
    commands = [command, '--nologo']
    commands.extend(args)
#    print commands

    # run command and wait for it to finish
    proc = subprocess.Popen(
        commands,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE
    )
    proc.wait()

    # if we do not want the value from stdout
    # then simply return the return code
    if not kwargs.get('stdout', True):
        return proc.returncode

    # return data from stdout
    output = []
    data = proc.stdout.read()

    # split output based on linesep and
    # add lines that are not blank
    data = data.split(os.linesep)

    for line in data:
        line = line.strip()

        # skip blank lines
        if line:
            output.append(line)

    return output
# end run