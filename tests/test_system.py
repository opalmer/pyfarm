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
from pyfarm.ext.system import (user, operating_system, network,
                               processor, memory)


class OperatingSystem(TestCase):
    def test_user(self):
        try:
            import pwd
            sysuser = pwd.getpwuid(os.getuid())[0]

        except ImportError:
            import getpass
            sysuser = getpass.getuser()

        self.assertEqual(user(), sysuser)

    def test_uptime(self):
        t1 = operating_system.uptime()
        t2 = time.time() - psutil.BOOT_TIME
        self.assertEqual(t2 - t1 < 5, True)

    def test_classvars(self):
        osenum = _OperatingSystem()
        _os = osenum.get()
        self.assertEqual(operating_system.OS, _os)
        self.assertEqual(operating_system.IS_LINUX, _os == osenum.LINUX)
        self.assertEqual(operating_system.IS_WINDOWS, _os == osenum.WINDOWS)
        self.assertEqual(operating_system.IS_MAC, _os == osenum.MAC)
        self.assertEqual(operating_system.IS_OTHER, _os == osenum.OTHER)
        self.assertEqual(operating_system.IS_POSIX,
                         _os in (osenum.LINUX, osenum.MAC))

    def test_case_sensitive(self):
        fid, path = tempfile.mkstemp()
        exists = map(os.path.isfile, [path, path.lower(), path.upper()])
        if not any(exists):
            raise ValueError("failed to determine if path was case sensitive")
        elif all(exists):
            self.assertEqual(operating_system.CASE_SENSITIVE, False)
        elif exists.count(True) == 1:
            self.assertEqual(operating_system.CASE_SENSITIVE, True)

        self.remove(path)

class Network(TestCase):
    def test_packets_sent(self):
        v = psutil.network_io_counters(
            pernic=True)[network.interface()].packets_sent
        self.assertEqual(network.packetsSent() >= v, True)

    def test_packets_recv(self):
        v = psutil.network_io_counters(
            pernic=True)[network.interface()].packets_recv
        self.assertEqual(network.packetsReceived() >= v, True)

    def test_data_sent(self):
        v = convert.bytetomb(psutil.network_io_counters(
            pernic=True)[network.interface()].bytes_sent)
        self.assertEqual(network.dataSent() >= v, True)

    def test_data_recv(self):
        v = convert.bytetomb(psutil.network_io_counters(
            pernic=True)[network.interface()].bytes_recv)
        self.assertEqual(network.dataReceived() >= v, True)

    def test_error_incoming(self):
        v = psutil.network_io_counters(pernic=True)[network.interface()].errin
        self.assertEqual(network.errorCountIncoming() >= v, True)

    def test_error_outgoing(self):
        v = psutil.network_io_counters(pernic=True)[network.interface()].errout
        self.assertEqual(network.errorCountOutgoing() >= v, True)

    def test_hostname(self):
        self.assertEqual(network.hostname(), socket.getfqdn())
        self.assertEqual(network.hostname(fqdn=False), socket.gethostname())

    def test_addresses(self):
        self.assertEqual(len(network.addresses()) >= 1, True)
        self.assertEqual(isinstance(network.addresses(), list), True)
        self.assertEqual(all(network.isPublic(a) for a in network.addresses()),
                         True)

    def test_interfaces(self):
        names = network.interfaces()
        self.assertEqual(len(names) > 1, True)
        self.assertEqual(isinstance(names, list), True)
        self.assertEqual(all(name in netifaces.interfaces() for name in names),
                         True)

        addresses = map(netifaces.ifaddresses, names)
        self.assertEqual(all(socket.AF_INET in i for i in addresses), True)

    def test_interface(self):
        self.assertEqual(any(
            i.get("addr") == network.ip()
            for i in netifaces.ifaddresses(
            network.interface()).get(socket.AF_INET, [])), True)

    def test_ip(self):
        self.assertEqual(network.isPublic(network.ip()), True)


class Processor(TestCase):
    def test_count(self):
        try:
            import multiprocessing
            cpu_count = multiprocessing.cpu_count()
        except (ImportError, NotImplementedError):
            cpu_count = psutil.NUM_CPUS

        self.assertEqual(processor.CPU_COUNT, cpu_count)

    @skip_on_ci
    def test_load(self):
        self.assertEqual(psutil.cpu_percent(.5) / processor.CPU_COUNT >= 0,
                         True)
        self.assertEqual(processor.load(.5) > 0, True)

    def test_usertime(self):
        self.assertEqual(psutil.cpu_times().user <= processor.userTime(),
                         True)

    def test_systemtime(self):
        self.assertEqual(psutil.cpu_times().system <= processor.systemTime(),
                         True)

    def test_idletime(self):
        self.assertEqual(psutil.cpu_times().idle <= processor.idleTime(),
                         True)

    def test_iowait(self):
        if operating_system.IS_LINUX:
            self.assertEqual(processor.iowait() <= psutil.cpu_times().iowait,
                             True)
        else:
            self.assertEqual(processor.iowait(), None)


class Memory(TestCase):
    def test_totalram(self):
        self.assertEqual(memory.TOTAL_RAM,
                         convert.bytetomb(psutil.TOTAL_PHYMEM))

    def test_totalswap(self):
        self.assertEqual(memory.TOTAL_SWAP,
                         convert.bytetomb(psutil.swap_memory().total))

    def test_swapused(self):
        v1 = convert.bytetomb(psutil.swap_memory().used)
        v2 = memory.swapUsed()
        self.assertEqual(v1-v2 < 5, True)

    def test_swapfree(self):
        v1 = convert.bytetomb(psutil.swap_memory().free)
        v2 = memory.swapFree()
        self.assertEqual(v1-v2 < 5, True)

    def test_ramused(self):
        v1 = convert.bytetomb(psutil.virtual_memory().used)
        v2 = memory.ramUsed()
        self.assertEqual(v1-v2 < 5, True)

    def test_ramfree(self):
        v1 = convert.bytetomb(psutil.virtual_memory().available)
        v2 = memory.ramFree()
        self.assertEqual(v1-v2 < 5, True)