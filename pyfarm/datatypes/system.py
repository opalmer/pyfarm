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
import tempfile
import psutil

from pyfarm.datatypes.enums import OperatingSystem
from pyfarm.utility import user
from pyfarm.datatypes.functions import bytes_to_megabytes

OS = OperatingSystem.get()
OSNAME = OperatingSystem.get(OS)
CPU_COUNT = psutil.NUM_CPUS
TOTAL_RAM = int(psutil.TOTAL_PHYMEM / 1024 / 1024)
USER = user()

# setup total swap and current swap
if hasattr(psutil, 'swap_memory'):
    TOTAL_SWAP = int(psutil.swap_memory().total / 1024 / 1024)
    swap = lambda: bytes_to_megabytes(psutil.swap_memory().free)

elif hasattr(psutil, 'virtmem_usage'):
    TOTAL_SWAP = int(psutil.virtmem_usage().total / 1024 / 1024)
    swap = lambda : bytes_to_megabytes(psutil.virtmem_usage().free)

else:
    TOTAL_SWAP = int(psutil.total_virtmem() / 1024 / 1024)
    swap = lambda: TOTAL_SWAP - bytes_to_megabytes(psutil.used_virtmem())

# setup current ram
if hasattr(psutil, 'virtual_memory'):
    ram = lambda: bytes_to_megabytes(psutil.virtual_memory().free)

elif hasattr(psutil, 'phymem_usage'):
    ram = lambda: bytes_to_megabytes(psutil.phymem_usage().free)

else:
    ram = lambda: TOTAL_RAM - bytes_to_megabytes(psutil.used_phymem())

# docstrings for swap and ram
swap.__doc__ = "returns the current swap available"
ram.__doc__ = "returns the current ram available"

# determine if the filesystem is case sensitive
_id, _tempname = tempfile.mkstemp()
FILE_CASE_SENSITIVE = not os.path.isfile(_tempname.upper())
