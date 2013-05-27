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
This plugin installs a SKIP error class for the SkipTest exception.
When SkipTest is raised, the exception will be logged in the skipped
attribute of the result, 'S' or 'SKIP' (verbose) will be output, and
the exception will not be counted as an error or failure. This plugin
is enabled by default but may be disabled with the ``--no-skip`` option.

.. note::
    This code has been copied from
    http://nose.readthedocs.org/en/latest/plugins/skip.html
"""

from nose.plugins.errorclass import ErrorClass, ErrorClassPlugin

try:
    # 2.7
    from unittest.case import SkipTest
except ImportError:
    # 2.6 and below
    class SkipTest(Exception):
        """raise this exception to mark a test as skipped."""


class Skip(ErrorClassPlugin):
    """
    Plugin that installs a SKIP error class for the SkipTest
    exception.  When SkipTest is raised, the exception will be logged
    in the skipped attribute of the result, 'S' or 'SKIP' (verbose)
    will be output, and the exception will not be counted as an error
    or failure.
    """
    enabled = True
    skipped = ErrorClass(SkipTest,
                         label='SKIP',
                         isfailure=False)

    def options(self, parser, env):
        """
        Add my options to command line.
        """
        env_opt = 'NOSE_WITHOUT_SKIP'
        parser.add_option('--no-skip', action='store_true',
                          dest='noSkip', default=env.get(env_opt, False),
                          help="Disable special handling of SkipTest "
                          "exceptions.")

    def configure(self, options, conf):
        """
        Configure plugin. Skip plugin is enabled by default.
        """
        if not self.can_configure:
            return
        self.conf = conf
        disable = getattr(options, 'noSkip', False)
        if disable:
            self.enabled = False