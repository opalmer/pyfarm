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
from os import sep
from os.path import dirname, join
import Info

# TODO: Turn this line conversion into a working class! SEE ALSO: crlf.py & lfcr.py which come with python
# TODO: Add other 'file utilities' to Util lib
class ConvertLineEndings(object):
    '''
    Used to Convert unix line endings to windows line endings
    and vise-versa

    VARIABLES:
        os (string) -- Target operating system
        target (string) -- Target file to run operation on
    '''
    def __init__(self,  os,  target):
        self.os = os.upper()
        self.target = target

    def run(self):
        '''Run the operation on the specified file'''
        for line in self.target.readline():
            if self.os == 'UNIX':
                    temp = string.replace(temp, '\r\n', '\n')
                    temp = string.replace(temp, '\r', '\n')
            elif self.os == 'MAC':
                    temp = string.replace(temp, '\r\n', '\r')
                    temp = string.replace(temp, '\n', '\r')
            elif self.os == 'DOS':
                    import re
                    temp = re.sub("\r(?!\n)|(?<!\r)\n", "\r\n", temp)
            return temp


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
