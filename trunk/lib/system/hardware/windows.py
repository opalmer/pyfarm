'''
HOMEPAGE: www.pyfarm.net
INITIAL: March 29 2011
PURPOSE: To query and return information about the local system (windows)

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
import sys

if os.name != "nt":
    print "FAIL: Cannot import the windows hardware library onto %s!!" % os.name
    sys.exit(1)

import re
import _winreg
import subprocess
import platform
import ctypes
import types

kernel32 = ctypes.windll.kernel32
mpr      = ctypes.windll.mpr

class _SysStruct(ctypes.Structure):
    _fields_ = [
                    ("wProcessorArchitecture", ctypes.c_ushort),
                    ("wReserved", ctypes.c_ushort)
               ]


class _SysUnion(ctypes.Union):
    _fields_ = [
                    ("dwOemId", ctypes.c_uint),
                    ("struct", _SysStruct)
                ]


class _System(ctypes.Structure):
    '''Return information about the system'''
    _fields_ = [
                    ("union", _SysUnion),
                    ("dwPageSize", ctypes.c_uint),
                    ("lpMinimumApplicationAddress", ctypes.c_void_p),
                    ("lpMaximumApplicationAddress", ctypes.c_void_p),
                    ("dwActiveProcessorMask", ctypes.c_void_p),
                    ("dwNumberOfProcessors", ctypes.c_uint),
                    ("dwProcessorType", ctypes.c_uint),
                    ("dwAllocationGranularity", ctypes.c_uint),
                    ("wProcessorLevel", ctypes.c_ushort),
                    ("wProcessorRevision", ctypes.c_ushort)
                ]


class _Memory(ctypes.Structure):
    '''
    Return information about the memory on the current system

    dwLength                - size of struct
    dwMemoryLoad            - approximate memory in use
    ullTotalPhys            - total physical memory
    ullAvailPhys            - total available memory
    ullTotalPageFile        - system memory limit
    ullAvailPageFile        - process memory limit
    ullTotalVirtual         - size of virtual address space
    ullAvailVirtual         - size of available memory
    ullAvailExtendedVirtual - reserved memory
    '''
    _fields_ = [
                    ("dwLength", ctypes.c_uint),
                    ("dwMemoryLoad", ctypes.c_uint),
                    ("ullTotalPhys", ctypes.c_uint64),
                    ("ullAvailPhys", ctypes.c_uint64),
                    ("ullTotalPageFile", ctypes.c_uint64),
                    ("ullAvailPageFile", ctypes.c_uint64),
                    ("ullTotalVirtual", ctypes.c_uint64),
                    ("ullAvailVirtual", ctypes.c_uint64),
                    ("ullAvailExtendedVirtual", ctypes.c_uint64)
                ]

def _kBToMB(kB): return kB / 1024
def _kbToMB(kB): return kb / 1024 / 1024

def _cpuInfo():
    '''Return a dictionary from the registry with cpu information'''
    cpu    = {}
    key    = "HARDWARE\\DESCRIPTION\\System\\CentralProcessor\\0"
    regKey = _winreg.OpenKey(_winreg.HKEY_LOCAL_MACHINE, key)

    for num in range(_winreg.QueryInfoKey(regKey)[1]):
        name, value, keyType = _winreg.EnumValue(regKey, num)

        # remove unicode and extra spaces
        rmUnicode = re.sub(r'''[^\x00-\x7F]+''', r'''''',  str(value)).strip()
        strip     = re.sub(r'''\s+''', r''' ''', rmUnicode)
        cpu[name] = strip

    regKey.Close()
    return cpu

def _memoryInfo():
    '''Return and up to date memory information object'''
    memory = _Memory()
    memory.dwLength = ctypes.sizeof(memory)
    kernel32.GlobalMemoryStatusEx(ctypes.byref(memory))
    return memory

# setup ctype objects
_cpu    = _cpuInfo()
_system = _System()
_struct = _SysStruct()
_memory = _memoryInfo()
kernel32.GetSystemInfo(ctypes.byref(_system))

def cpuCount():
    '''
    Return the number of processors installed in the system.  Please note that
    this count includes logical as well as physical processors.
    '''
    return _system.dwNumberOfProcessors

def cpuType():
    '''Return the type of cpu current installed'''
    return _cpu['ProcessorNameString']

def cpuSpeed():
    '''
    Return the cpu speed as the operating system sees it.  Please note that
    some CPUs are throttled down when not in use so this value may appear
    lowr than it really is.
    '''
    return int(_cpu['~MHz'])

def ramTotal():
    '''Return the total about of physical ram installed (in MB)'''
    return _kbToMB(_memoryInfo().ullTotalPhys)

def ramFree():
    '''Return the current amount of physical RAM available for use'''
    return _kbToMB(_memoryInfo().ullAvailPhys)

def swapTotal():
    '''Return the size of the swap'''
    return _kbToMB(_memoryInfo().ullTotalPageFile)-ramTotal()

def swapFree():
    '''Return the amount of swap free'''
    return _kbToMB(_memoryInfo().ullAvailVirtual)-swapTotal()

def load():
    '''Return the average system load'''
    return 0, 0, 0

def uptime():
    '''
    Return the total amount of time in seconds that the system has been
    online
    '''
    return 0

def idletime():
    '''
    Return the total amout of time in seconds that the system has spent idle
    since the last boot
    '''
    return 0

def osName():
    '''Operating system name based on current file name'''
    return os.path.basename(__file__).split('.')[0]

def osVersion():
    '''Version of the operating system or kernel installed'''
    return platform.uname()[2]

def architecture():
    '''Return the system architecture'''
    if platform.architecture()[0] == "64bit":
        return "x86_64"
    return "i686"

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
