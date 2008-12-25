'''
AUTHOR: Oliver Palmer
CONTACT: opalme20@student.scad.edu || (703)725-6544
INITIAL: Dec 18 2008
PURPOSE: Module contains multiple utilities used both by PyFarm and for
general usage.
'''
import string

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
        self.os = string.upper(os)
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
        return [s[i*c:(i+1)*c] for i in range((len(s)+c-1)/c]
