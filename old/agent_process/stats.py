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


import psutil

from pyfarm.logger import Logger
from pyfarm.datatypes.objects import ScheduledRun
from pyfarm.config.core import Loader

# TODO: needs a refactor and removal/update to the logger
class MemorySurvey(ScheduledRun, Logger):
    def __init__(self):
        prefs = Loader("agent.yml")
        ScheduledRun.__init__(self, prefs.get('proc-mem-survey-interval'))
        Logger.__init__(self, self)

        self.process = None
        self.pid = None
        self.vms = []
        self.rss = []

    def setup(self, pid):
        if self.process is None:
            self.process = psutil.Process(pid)
            self.pid = pid

    def run(self, force=False):
        if self.process is None:
            self.error("process has not been setup yet")

        elif self.shouldRun(force):
            meminfo = self.process.get_memory_info()
            vms = [meminfo.vms]
            rss = [meminfo.rss]

            for process in self.process.get_children(recursive=True):
                child_meminfo = process.get_memory_info()
                vms.append(child_meminfo.vms)
                rss.append(child_meminfo.rss)

            # get the total ram usage across all processes (children
            # included)
            vms = sum(vms) / 1024 / 1024
            rss = sum(rss) / 1024 / 1024

            # since could be
            if vms not in self.vms:
                self.vms.append(vms)

            if rss not in self.rss:
                self.rss.append(rss)

            args = (self.pid, vms, rss)
            self.debug(
                "ran memory survey for parent %s (vms: %s, rss: %s)" % args
            )
