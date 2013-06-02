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

__version__ = (1, 0, 0)
__versionstr__ = ".".join(map(str, __version__))
__author__ = "Oliver Palmer"

# create a dist variable which we can use in the package
# to retrieve some location information about the installation
import pkg_resources
try:
    dist = pkg_resources.get_distribution("pyfarm")

except pkg_resources.DistributionNotFound:
    raise pkg_resources.DistributionNotFound(
        "failed to find the `pyfarm` package, has it been installed with pip?"
    )
