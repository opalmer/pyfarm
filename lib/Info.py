'''
AUTHOR: Oliver Palmer
HOMEPAGE: www.pyfarm.net
INITIAL: Dec 8 2008
PURPOSE: Module used to return miscellaneous info either about the system
or PyFarm itself.

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
    from os import loadavg
except ImportError:
    pass

finally:
    from os import sep, name, getenv
    import uuid
    import time
    import socket
    from random import randint
    from subprocess import Popen,PIPE
    from os.path import dirname, join
    from PyQt4.QtCore import QObject, QThread
    from PyFarmExceptions import ErrorProcessingSetup

def Int2Time(s):
        '''Given an input integer, return time elapsed'''
        #s=ms/1000
        m,s=divmod(s,60)
        h,m=divmod(m,60)
        d,h=divmod(h,24)
        return d,h,m,s


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


class System(object):
    '''
    Return important information about the system
    '''
    def __init__(self):
        super(System,  self).__init__()

    def time(self, format ):
        '''Return the current system time to the user'''
        return time.strftime("%d %b %Y %H:%M:%S")

    def os(self):
        '''
        Get the type of os and architecture

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
            output.append('win')
            output.append(getenv('PROCESSOR_ARCHITECTURE'))

        return output

    def load(self):
        '''
        Return the average CPU load to the user
        '''
        if self.os() == 'linux':
            return loadavg()

        elif self.os() == 'windows':
            # there is a not a way to do this on windows...yet
            pass

    def coreCount(self):
        '''Return the number of cores installed on the system'''
        # OS X: sysctl -n hw.logicalcpu
        # Win: getEnv(NUMBER_OF_PROCESSORS)
        # Linux: cat /proc/cpuinfo | grep siblings | awk {'print $3'}

    def hostname(self):
        '''Return the name of the computer'''
        return socket.gethostname()

    def macAddress(self):
        '''Return a list of mac address to the user'''
        mac = []

        if self.os() == 'linux':
            p = Popen(['ifconfig | grep HWaddr | awk {"print $5"}'],shell=True, stdout=PIPE)

            while True:
                line = p.stdout.readline()
                mac.append(line.split('\n')[0][len(line)-20:])
                if line == '' and p.poll() != None: break

        elif self.os() == 'windows':
            p = Popen(['ipconfig'])
        return mac


class Stopwatch(QThread):
    '''Threaded stopwatch for general usage'''
    def __init(self, parent=None):
        super(Stopwatch, self).__init__(parent)

    def run(self):
        '''Start the stopwatch'''
        self.start = time.time()
        while 1:
            time.sleep(1)
            print self.elapsed()

    def stop(self):
        '''Stop the stopwatch'''
        self.stop = time.time()

    def elapsed(self, formatted=True):
        '''Return the elapsed time since start'''
        if formatted:
            return Int2Time(time.time()-self.start)

class Numbers(object):
    '''
    Provides several functions for creating, converting,
    and evaling numbers
    '''
    def __init__(self):
        pass

    def int2hex(self, intIn):
        '''Convert an integer to hexadecimal'''
        return "%x" % intIn

    def hex2int(self, inHex):
        '''Convert an hexadecimal to integer'''
        return int(inHex, 16)

    def randhex(self, s, e):
        '''Produces hex value based on time'''
        return "%x" % randint(s, e)

    def randint(self, s, e):
        '''Produces rand int based on time'''
        return randint(s, e)

    def hexid(self):
        '''Return a hex id based on time.time()'''
        return self.int2hex(time.time())


class File(object):
    '''
    Large file class meant to handle multiple tasks
    including readline, file size, get extension, etc.
    '''
    def __init__(self, file):
        self.file = file

    def ext(self):
        '''Return the extension of the file'''
        return self.file.split('.')[len(self.file.split('.'))-1]


class Statistics(object):
    '''
    Statistics class used to calculate and return
    information about an input data set.
    '''
    def __init__(self, parent=None):
        super(Statistics, self).__init__(parent)
        moduleName = 'Info.Statistics'
        self.error = ErrorProcessingSetup(moduleName)
        self.typeTest = TypeTest()

    def get(self, data, callIn):
        '''
        The main calculation thread.  Run calculation in a
        thread because of the amount of large

        INPUT:
            data (list) -- list of data to run calculation on
            callIn (str/list) -- list of calculations to run
        '''
        self.data = data
        allowedCalls = ["mean", "median", "mode", "min", "max"]
        output = []

        # if given a single command
        if type(callIn) == 'str':
            if callIn in allowedCalls:
                self._findData(callIn)
            else:
                self.error.stringError('mean, median, mode, min, or max', callIn)

        elif type(callIn) == 'list':
            for call in callIn:
                if call in allowedCalls:
                    output.append(self._findData(call))
                else:
                    self.error.stringError('mean, median, mode, min, or max', callIn)

        else:
            self.error.typeError('string or list', self.typeTest.getType(callIn))

        return output

    def _findData(self, func):
        '''Given a function name, run the corrent function'''
        if func == 'mean':
            return self.mean()
        elif func == 'median':
            return self.median()
        elif func == 'mode':
            return self.mode()
        elif func == 'min':
            return self.min()
        elif func == 'max':
            return self.max()

    def mean(self):
        '''Return the mean (average) of the given number set'''
        floatData = []
        dataLen = float(len(self.data))
        dataTotal = 0.0

        for data in self.data:
            dataTotal += float(data)

        print floatData
        return dataTotal / dataLen

    def median(self):
        '''Define the median (center) of the given data set'''
        pass

    def mode(self):
        '''Return the mode (most occurring) of the given number set'''
        # create an empty dictionary to hold the data
        frequency = {}

        # for each number(x) in the input list(numList)
        for x in self.data:
            # if the number is in the dictionary(frequency)
            # append the count of that number, else start
            # couting
            if x in frequency:
                frequency[x] += 1
            else:
                frequency[x] = 1

        # find the maxium value(s) in the dictionary
        mode = max(frequency.values())

        if mode == 1:
            mode = []
            return

        # loop over the data and return tuples with the mode data and value
        mode = [(x, mode) for x in frequency if (mode == frequency[x])]

        # return mode
        # [0][0] will return the key of the first tuple.
        # We only want the first tuple which is the lowest key, therefor
        # the 'safest' status to return.  Otherwise me might return failed as the
        # current status even though there are just as many rendering frames.
        return mode[0][0]

    def min(self):
        '''Return the minium value in the given data'''
        return min(self.data)

    def max(self):
        '''Return the maxium value in the given data'''
        return max(self.data)


class TypeTest(object):
    '''
    Test an object and determine if it matches the type
    we are looking for.  If it does not match our request
    raise an exception.

    INPUT:
        module (str) -- Module making the request
    '''
    def __init__(self, module=None):
        if module:
            self.error = ErrorProcessingSetup(module)

    def _typeMatch(self, item, expected):
        '''Return true if the type matches the expected value'''
        if type(item) == expected:
            return True
        else:
            return False

    def getType(self, item):
        '''Return the type of the item'''
        typeInstance = type(item)
        if typeInstance== str:
            return 'string'
        elif typeInstance== list:
            return 'list'
        elif typeInstance == dict:
            return 'dictionary'
        elif typeInstance == int:
            return 'integer'
        elif typeInstance == float:
            return 'float'
        elif typeInstance == long:
            return "long"
        else:
            return typeInstance

    def isString(self, item):
        '''Check and see if the given item is a string'''
        if self._typeMatch(item, str):
            return item
        else:
            raise self.error.typeError('string', self.getType(item))

    def isList(self, item):
        '''Check and see if the given item is a list'''
        if self._typeMatch(item, list):
            return item
        else:
            raise self.error.typeError('list', self.getType(item))

    def isDict(self, item):
        '''Check and see if the given item is a dictionary'''
        if self._typeMatch(item, dict):
            return item
        else:
            raise self.error.typeError('dictionary', self.getType(item))

    def isInt(self, item):
        '''Check and see if the given item is a integer'''
        if self._typeMatch(item, int):
            return item
        else:
            raise self.error.typeError('integer', self.getType(item))

    def isFloat(self, item):
        '''Check and see if the given item is a float'''
        if self._typeMatch(item, float):
            return item
        else:
            raise self.error.typeError('float', self.getType(item))

    def isLong(selfself, item):
        '''Check and see if the given item is a long int'''
        if self._typeMatch(item, long):
            return item
        else:
            raise self.error.typeError('long', self.getType(item))

if __name__ == '__main__':
    from random import randint

    numList = []
    num = 0
    while num <= 20:
        numList.append(randint(1, 6))
        num += 1

    stats = Statistics()
    stat = stats.get(numList, ['mean', 'min', 'max'])
    print "Average: %f\nMin: %i\nMax: %i"% (stat[0], stat[1], stat[2])
