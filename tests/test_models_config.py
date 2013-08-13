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

from utcore import TestCase
from pyfarm.ext.config.core.loader import Loader
from pyfarm.models.core import cfg


class Config(TestCase):    
    def test_config(self):
        self.assertTrue(isinstance(cfg.DBCFG, Loader))

    def test_table_prefixes(self):
        prefix = cfg.DBCFG.get("tables.prefix")
        for varname, value in vars(cfg).iteritems():
            if varname.startswith("TABLE_"):
                self.assertTrue(value.startswith(prefix))