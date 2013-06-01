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

from twisted.internet import protocol
from pyfarm.logger import Logger, Observer


class ProcessProtocol(protocol.ProcessProtocol, Logger):
    def __init__(self, process, arguments, log):
        Logger.__init__(self, self)
        self.process = process
        self.arguments = arguments

        # setup the logger
        self.observer = Observer(log)
        self.process_logger = Logger(
            stdout_observer=False, inherit_observers=False
        )
        self.addObserver(self.observer)
        self.process_logger.addObserver(self.observer)

    def connectionMade(self):
        """send a log message the the process log file"""
        self.transport.write(" ".join(self.arguments))
        self.transport.closeStdin()
        self.process.running = True

    def outReceived(self, data):
        self.process_logger.msg(data.strip(), level='STDOUT', system=None)

    def errReceived(self, data):
        self.process_logger.msg(data.strip(), level='STDERR', system=None)

    def processExited(self, reason):
        self.process.stopped(reason)
        self.removeObserver(self.observer)
