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

try:
    import multiprocessing
except ImportError:
    multiprocessing = None

try:
    import pwd
    _user = lambda: pwd.getpwuid(os.getuid())[0]

except ImportError:
    import getpass
    _user = lambda: getpass.getuser()

from nose.tools import eq_
from nose.plugins.skip import SkipTest
from pyfarm.ext.utility import convert
from pyfarm.ext.config.enum import OperatingSystem as _OperatingSystem
from pyfarm.ext.system import (user, operating_system, network,
                               processor, memory)

osenum = _OperatingSystem()


def test_user():
    eq_(user(), _user())


def test_os_uptime():
    t1 = operating_system.uptime()
    t2 = time.time() - psutil.BOOT_TIME
    eq_(t2 - t1 < 5, True)


def test_os_classvars():
    _os = osenum.get()
    eq_(operating_system.OS, _os)
    eq_(operating_system.IS_LINUX, _os == osenum.LINUX)
    eq_(operating_system.IS_WINDOWS, _os == osenum.WINDOWS)
    eq_(operating_system.IS_MAC, _os == osenum.MAC)
    eq_(operating_system.IS_OTHER, _os == osenum.OTHER)
    eq_(operating_system.IS_POSIX, _os in (osenum.LINUX, osenum.MAC))


def test_os_case_sensitive():
    fid, path = tempfile.mkstemp()
    exists = map(os.path.isfile, [path, path.lower(), path.upper()])
    if not any(exists):
        raise ValueError("failed to determine if path was case sensitive")
    elif all(exists):
        cs = False

    elif exists.count(True) == 1:
        cs = True
    try:
        os.remove(path)
    except:
        pass

    eq_(operating_system.CASE_SENSITIVE, cs)


def test_net_packets_sent():
    v = psutil.network_io_counters(
        pernic=True)[network.interface()].packets_sent
    eq_(network.packetsSent() >= v, True)


def test_net_packets_recv():
    v = psutil.network_io_counters(
        pernic=True)[network.interface()].packets_recv
    eq_(network.packetsReceived() >= v, True)


def test_net_data_sent():
    v = convert.bytetomb(psutil.network_io_counters(
        pernic=True)[network.interface()].bytes_sent)
    eq_(network.dataSent() >= v, True)


def test_net_data_recv():
    v = convert.bytetomb(psutil.network_io_counters(
        pernic=True)[network.interface()].bytes_recv)
    eq_(network.dataReceived() >= v, True)


def test_net_error_incoming():
    v = psutil.network_io_counters(pernic=True)[network.interface()].errin
    eq_(network.errorCountIncoming() >= v, True)


def test_net_error_outgoing():
    v = psutil.network_io_counters(pernic=True)[network.interface()].errout
    eq_(network.errorCountOutgoing() >= v, True)


def test_net_hostname():
    eq_(network.hostname(), socket.getfqdn())
    eq_(network.hostname(fqdn=False), socket.gethostname())


def test_net_addresses():
    eq_(len(network.addresses()) >= 1, True)
    eq_(isinstance(network.addresses(), list), True)
    eq_(all(network.isPublic(a) for a in network.addresses()), True)


def test_net_interfaces():
    names = network.interfaces()
    eq_(len(names) > 1, True)
    eq_(isinstance(names, list), True)
    eq_(all(name in netifaces.interfaces() for name in names), True)

    addresses = map(netifaces.ifaddresses, names)
    eq_(all(socket.AF_INET in i for i in addresses), True)


def test_net_interface():
    eq_(any(
        i.get("addr") == network.ip()
        for i in netifaces.ifaddresses(
        network.interface()).get(socket.AF_INET, [])), True)


def test_net_ip():
    eq_(network.isPublic(network.ip()), True)


def test_processor_count():
    count = None
    if multiprocessing is not None:
        try:
            count = multiprocessing.cpu_count()
        except NotImplementedError:
            count = None

    if count is None:
        count = psutil.NUM_CPUS

    eq_(processor.CPU_COUNT, count)


def test_processor_load():
    # skip for now, add things happen when running on a vm
    if "TRAVIS" in os.environ or "BUILD_UUID" in os.environ:
        raise SkipTest

    eq_(psutil.cpu_percent(.5) / processor.CPU_COUNT >= 0, True)
    eq_(processor.load(.5) > 0, True)


def test_processor_usertime():
    eq_(psutil.cpu_times().user <= processor.userTime(), True)


def test_processor_systemtime():
    eq_(psutil.cpu_times().system <= processor.systemTime(), True)


def test_processor_idletime():
    eq_(psutil.cpu_times().idle <= processor.idleTime(), True)


def test_processor_iowait():
    if operating_system.IS_POSIX:
        eq_(processor.iowait() <= psutil.cpu_times().iowait, True)
    else:
        eq_(processor.iowait(), None)


def test_memory_totalram():
    eq_(memory.TOTAL_RAM, convert.bytetomb(psutil.TOTAL_PHYMEM))


def test_memory_totalswap():
    eq_(memory.TOTAL_SWAP, convert.bytetomb(psutil.swap_memory().total))


def test_memory_swapused():
    v1 = convert.bytetomb(psutil.swap_memory().used)
    v2 = memory.swapUsed()
    eq_(v1-v2 < 5, True)


def test_memory_swapfree():
    v1 = convert.bytetomb(psutil.swap_memory().free)
    v2 = memory.swapFree()
    eq_(v1-v2 < 5, True)


def test_memory_ramused():
    v1 = convert.bytetomb(psutil.virtual_memory().used)
    v2 = memory.ramUsed()
    eq_(v1-v2 < 5, True)


def test_memory_ramfree():
    v1 = convert.bytetomb(psutil.virtual_memory().available)
    v2 = memory.ramFree()
    eq_(v1-v2 < 5, True)