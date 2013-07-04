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

"""
Contains information about the processor and its relation to the operating
system such as load, processing times, etc.
"""

import psutil

try:
    from multiprocessing import cpu_count
    NUM_CPUS = cpu_count()

except (ImportError, NotImplementedError):
    NUM_CPUS = psutil.NUM_CPUS


class ProcessorInfo(object):
    """
    .. note::
        This class has already been instanced onto `pyfarm.system.processor`

    Namespace class which returns information about the processor(s)
    in use on the system.

    :attr CPU_COUNT:
        Returns the total number of cpus installed.  This first
        attempts to use :func:`multiprocessing.cpu_count` before
        falling back onto `psutil.NUM_CPUS`
    """
    NUM_CPUS = NUM_CPUS

    def load(self, iterval=1):
        """
        Returns the load across all cpus value from zero to one.  A value
        of 1.0 means the average load across all cpus is 100%.
        """
        return psutil.cpu_percent(iterval) / self.NUM_CPUS

    def userTime(self):
        """
        Returns the amount of time spent by the cpu in user
        space
        """
        return psutil.cpu_times().user

    def systemTime(self):
        """
        Returns the amount of time spent by the cpu in system
        space
        """
        return psutil.cpu_times().system

    def idleTime(self):
        """
        Returns the amount of time spent by the cpu in idle
        space
        """
        return psutil.cpu_times().idle

    def iowait(self):
        """
        Returns the amount of time spent by the cpu waiting
        on io

        .. note::
            on platforms other than linux this will return None
        """
        try:
            cpu_times = psutil.cpu_times()
            if hasattr(cpu_times, "iowait"):
                return psutil.cpu_times().iowait
        except AttributeError:
            return None