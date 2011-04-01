'''
HOMEPAGE: www.pyfarm.net
INITIAL: March 29 2011
PURPOSE: To query and return information about the local system (linux)

This file is part of PyFarm.
Copyright (C) 2008-2011 Oliver Palmer

PyFarm is free software: you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

PyFarm is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public License
along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.
'''
import os
import types

def _procInfo(path, key):
    '''Generator object to return specific lines with CPU information'''
    try:
        for line in open(path, 'r'):
            if line.startswith(key):
                yield line

    except Exception, error:
        print error

def cpuCount():
    '''
    Return the number of processors installed in the system.  Please note that
    this count includes logical as well as physical processors.
    '''
    count = 0
    for line in _procInfo("/proc/cpuinfo", "processor"):
        if line:
            count += 1

    return count

def cpuSpeed():
    '''
    Return the cpu speed as the operating system sees it.  Please note that
    some CPUs are throttled down when not in use so this value may appear
    lowr than it really is.
    '''
    speed = 0
    for line in _procInfo("/proc/cpuinfo", "cpu MHz"):
        if line:
            try:
                speed = max(float(line[line.find(":")+1:]), speed)

            except ValueError:
                pass

    # attempt to find a more accurate cpu speed
    maxFreq = "/sys/devices/system/cpu/cpu0/cpufreq/cpuinfo_max_freq"

    if os.path.exists(maxFreq):
        try:
            freq  = open(maxFreq, "r")
            speed = float(freq.read().strip()) / 1024
            freq.close()

        except:
            pass

    return speed

def ramTotal():
    '''Return the total about of physical ram installed (in KB)'''
    ram = 0

    for line in _procInfo("/proc/meminfo", "MemTotal"):
        if line:
            try:
                ram = int(line[line.find(":")+1:-3])

            except:
                pass

    return ram

def ramFree():
    '''Return the current amount of physical RAM available for use'''
    ram = 0
    try:
        for line in _procInfo("/proc/meminfo", "MemFree"):
            if line:
                ram = int(line[line.find(":")+1:-3])
    except:
        pass

    return ram

def swapTotal():
    '''Return the size of the swap'''
    swap = 0

    for line in _procInfo("/proc/meminfo", "SwapTotal"):
        if line:
            try:
                swap = int(line[line.find(":")+1:-3])
            except:
                pass

    return swap

def swapFree():
    '''Return the amount of swap free'''
    swap = 0

    for line in _procInfo("/proc/meminfo", "SwapFree"):
        if line:
            try:
                swap = int(line[line.find(":")+1:-3])
            except:
                pass

    return swap

def load():
    '''Return the average system load'''
    return os.getloadavg()

def uptime():
    '''
    Return the total amount of time in seconds that the system has been
    online
    '''
    up = 0

    try:
        upFile = open('/proc/uptime', 'r').readlines()[0].split()
        up     = upFile[0]
    except:
        pass

    return float(up)

def idletime():
    '''
    Return the total amout of time in seconds that the system has spent idle
    since the last boot
    '''
    up = 0

    try:
        upFile = open('/proc/uptime', 'r').readlines()[0].split()
        up     = upFile[1]
    except:
        pass

    return float(up)

def osName():
    '''Operating system name based on current file name'''
    return os.path.basename(__file__).split('.')[0]

def osVersion():
    return os.uname()[2]

def architecture():
    '''Return the system architecture'''
    return os.uname()[4]

def report():
    '''Report all hardware information in the form of a dictionary'''
    output = {}

    for key, value in globals().items():
        isFunction = type(value) == types.FunctionType
        isPrivate  = key.startswith("_")
        isReport   = key == "report"

        if isFunction and not isPrivate and not isReport:
            output[key] = value()

    return output


if __name__ == '__main__':
    print
    print "                 %s SYSTEM INFORMATION" % osName().upper()
    for key, value in report().items():
        print "%25s | %s" % (key, value)