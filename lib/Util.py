'''
AUTHOR: Oliver Palmer
CONTACT: oliverpalmer@opalmer.com
INITIAL: Dec 18 2008
PURPOSE: Module contains multiple utilities used both by PyFarm and for
general usage.

    This file is part of PyFarm.

    PyFarm is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    PyFarm is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.

'''
try:
    from os import uname

# if we get an import error pass it
except ImportError:
    pass

finally:
    from os import sep, name, getenv
    from os.path import dirname, join
    import Info

class StringUtil(object):
    '''General utilities for string formatting'''
    def __init__(self):
        super(StringUtil,  self).__init__()

    def chop(s, c):
        '''Chop string s into c number of chunks'''
        return [s[i*c:(i+1)*c] for i in range((len(s)+c-1))/c] # added a ) after c-1)

    def removeLineEnding(self, str):
        '''Given a input string, remove the appropriate line ending'''
        if Info.os() == 'linux':
            return str.split('\n')[0]
        elif Info.os() == 'windows':
            return str.split('\r\n')[0]


def ModulePath(module, level=0):
    '''Given a module return it's path to the n'th level'''
    if level == 0:
        return dirname(module)+'/'

    else:
        OUT = ''
        path = dirname(module).split(sep)

        for i in path[:len(path)-level]:
            OUT += '%s/' % i

        return OUT

def GetOs():
    '''
    Get the type of os and the architecture

    OUTPUT:
        [ operating_system, arhitecture ]
    '''
    output = []

    if name == 'posix' and uname()[0] != 'Darwin':
        output.append('linux')
        if uname()[4] == 'x86_64':
            output.append('x64')
        elif uname()[4] == 'i386' or uname()[4] == 'i686' or uname()[4] == 'x86':
            output.append('x86')

    # if mac, do this
    elif name == 'posix' and uname()[0] == 'Darwin':
        output.append('mac')
        if uname()[4] == 'i386' or uname[4] == 'i686':
            output.append('x86')

    elif name == 'nt':
        output.append('windows')
        output.append(getenv('PROCESSOR_ARCHITECTURE'))


    # finally return the output
    return output
