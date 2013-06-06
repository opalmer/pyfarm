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
Module used for providing extensions to PyFarm.  For example
the code to produce temporary files and directories could
be loaded using the below instead of a direct import

.. note::
    Internally PyFarm attempts to use this system prior to falling back
    on its own functions.  This may not be the case in all situations however.

.. seealso::
    For the implementation, see :mod:`pyfarm.exthook`
"""


def setup():
    from pyfarm.exthook import ExtensionImporter
    importer = ExtensionImporter(ExtensionImporter.DEFAULT_CHOICES, __name__)
    importer.install()

setup()
del setup
