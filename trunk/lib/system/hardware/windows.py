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

if os.uname != "nt":
    print "FAIL: Cannot import the windows hardware library onto %s!!" % os.name
    sys.exit(1)

import _winreg
import subprocess
import platform
import ctypes

kernel32 = ctypes.windll.kernel32
mpr      = ctypes.windll.mpr

class SysStruct(ctypes.Structure):
    _fields_ = [
                    ("wProcessorArchitecture", ctypes.c_ushort),
                    ("wReserved", ctypes.c_ushort)
               ]


class SysUnion(ctypes.Union):
    _fields_ = [
                    ("dwOemId", ctypes.c_uint),
                    ("struct", SysStruct)
                ]


class System(ctypes.Structure):
    '''Return information about the system'''
    _fields_ = [
                    ("union", SysUnion),
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


class Memory(ctypes.Structure):
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


def cpuCount():
    '''
    Return the number of processors installed in the system.  Please note that
    this count includes logical as well as physical processors.
    '''
    sysInfo = System()
    return sysInfo.dwNumberOfProcessors

def cpuSpeed():
    '''
    Return the cpu speed as the operating system sees it.  Please note that
    some CPUs are throttled down when not in use so this value may appear
    lowr than it really is.
    '''
    return 0

def ramTotal(): return 0
def ramFree(): return 0
def swapTotal(): return 0
def swapFree(): return 0
def load(): return 0, 0, 0
def uptime(): return 0
def idletime(): return 0
def osName(): return os.path.basename(__file__).split('.')[0]
def osVersion(): return 0

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



# cleanup objects specific to this module
del kernel32, mpr, _winreg, ctypes


if __name__ == '__main__':
    print
    print "                 %s SYSTEM INFORMATION" % osName().upper()
    for key, value in report().items():
        print "%25s | %s" % (key, value)