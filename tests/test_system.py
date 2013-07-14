# No shebang line, this module is meant to be imported
#
# Copyright 2013 Oliver Palmer
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#    http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.

import os
import time
import socket
import psutil
import tempfile
import netifaces

from utcore import TestCase, skip_on_ci
from pyfarm.ext.utility import convert
from pyfarm.ext.config.enum import OperatingSystem as _OperatingSystem
from pyfarm.ext.system import osinfo, netinfo, cpuinfo, meminfo, username


class OperatingSystem(TestCase):
    def test_user(self):
        try:
            import pwd
            sysuser = pwd.getpwuid(os.getuid())[0]

        except ImportError:
            import getpass
            sysuser = getpass.getuser()

        self.assertEqual(username(), sysuser)

    def test_uptime(self):
        t1 = osinfo.uptime()
        t2 = time.time() - psutil.BOOT_TIME
        self.assertEqual(t2 - t1 < 5, True)

    def test_classvars(self):
        osenum = _OperatingSystem()
        _os = osenum.get()
        self.assertEqual(osinfo.OS, _os)
        self.assertEqual(osinfo.IS_LINUX, _os == osenum.LINUX)
        self.assertEqual(osinfo.IS_WINDOWS, _os == osenum.WINDOWS)
        self.assertEqual(osinfo.IS_MAC, _os == osenum.MAC)
        self.assertEqual(osinfo.IS_OTHER, _os == osenum.OTHER)
        self.assertEqual(osinfo.IS_POSIX,
                         _os in (osenum.LINUX, osenum.MAC))

    def test_case_sensitive(self):
        fid, path = tempfile.mkstemp()
        exists = map(os.path.isfile, [path, path.lower(), path.upper()])
        if not any(exists):
            raise ValueError("failed to determine if path was case sensitive")
        elif all(exists):
            self.assertEqual(osinfo.CASE_SENSITIVE, False)
        elif exists.count(True) == 1:
            self.assertEqual(osinfo.CASE_SENSITIVE, True)

        self.remove(path)

class Network(TestCase):
    def test_packets_sent(self):
        v = psutil.net_io_counters(
            pernic=True)[netinfo.interface()].packets_sent
        self.assertEqual(netinfo.packetsSent() >= v, True)

    def test_packets_recv(self):
        v = psutil.net_io_counters(
            pernic=True)[netinfo.interface()].packets_recv
        self.assertEqual(netinfo.packetsReceived() >= v, True)

    def test_data_sent(self):
        v = convert.bytetomb(psutil.net_io_counters(
            pernic=True)[netinfo.interface()].bytes_sent)
        self.assertEqual(netinfo.dataSent() >= v, True)

    def test_data_recv(self):
        v = convert.bytetomb(psutil.net_io_counters(
            pernic=True)[netinfo.interface()].bytes_recv)
        self.assertEqual(netinfo.dataReceived() >= v, True)

    def test_error_incoming(self):
        v = psutil.net_io_counters(pernic=True)[netinfo.interface()].errin
        self.assertEqual(netinfo.errorCountIncoming() >= v, True)

    def test_error_outgoing(self):
        v = psutil.net_io_counters(pernic=True)[netinfo.interface()].errout
        self.assertEqual(netinfo.errorCountOutgoing() >= v, True)

    def test_hostname(self):
        self.assertEqual(netinfo.hostname(), socket.getfqdn())
        self.assertEqual(netinfo.hostname(fqdn=False), socket.gethostname())

    def test_addresses(self):
        self.assertEqual(len(netinfo.addresses()) >= 1, True)
        self.assertEqual(isinstance(netinfo.addresses(), list), True)

    def test_interfaces(self):
        names = netinfo.interfaces()
        self.assertEqual(len(names) > 1, True)
        self.assertEqual(isinstance(names, list), True)
        self.assertEqual(all(name in netifaces.interfaces() for name in names),
                         True)

        addresses = map(netifaces.ifaddresses, names)
        self.assertEqual(all(socket.AF_INET in i for i in addresses), True)

    def test_interface(self):
        self.assertEqual(any(
            i.get("addr") == netinfo.ip()
            for i in netifaces.ifaddresses(
            netinfo.interface()).get(socket.AF_INET, [])), True)


class Processor(TestCase):
    def test_count(self):
        try:
            import multiprocessing
            cpu_count = multiprocessing.cpu_count()
        except (ImportError, NotImplementedError):
            cpu_count = psutil.NUM_CPUS

        self.assertEqual(cpuinfo.NUM_CPUS, cpu_count)

    def test_usertime(self):
        self.assertEqual(psutil.cpu_times().user <= cpuinfo.userTime(),
                         True)

    def test_systemtime(self):
        self.assertEqual(psutil.cpu_times().system <= cpuinfo.systemTime(),
                         True)

    def test_idletime(self):
        self.assertEqual(psutil.cpu_times().idle <= cpuinfo.idleTime(),
                         True)

    def test_iowait(self):
        if osinfo.IS_LINUX:
            self.assertEqual(cpuinfo.iowait() <= psutil.cpu_times().iowait,
                             True)
        else:
            self.assertEqual(cpuinfo.iowait(), None)


class Memory(TestCase):
    def test_totalram(self):
        self.assertEqual(meminfo.TOTAL_RAM,
                         convert.bytetomb(psutil.TOTAL_PHYMEM))

    def test_totalswap(self):
        self.assertEqual(meminfo.TOTAL_SWAP,
                         convert.bytetomb(psutil.swap_memory().total))

    def test_swapused(self):
        v1 = convert.bytetomb(psutil.swap_memory().used)
        v2 = meminfo.swapUsed()
        self.assertEqual(v1-v2 < 5, True)

    def test_swapfree(self):
        v1 = convert.bytetomb(psutil.swap_memory().free)
        v2 = meminfo.swapFree()
        self.assertEqual(v1-v2 < 5, True)

    def test_ramused(self):
        v1 = convert.bytetomb(psutil.virtual_memory().used)
        v2 = meminfo.ramUsed()
        self.assertEqual(v1-v2 < 5, True)

    def test_ramfree(self):
        v1 = convert.bytetomb(psutil.virtual_memory().available)
        v2 = meminfo.ramFree()
        self.assertEqual(v1-v2 < 5, True)