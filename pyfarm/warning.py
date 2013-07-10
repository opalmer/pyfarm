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
Contains warning classes which PyFarm may emit during
execution
"""


class PyFarmWarning(Warning):
    """Base class which all other warning related to PyFarm subclass from"""


class NetworkWarning(PyFarmWarning):
    """
    Emitted when we have a non-error condition related to
    network problems or unexpected issues
    """


class ConfigurationWarning(PyFarmWarning):
    """
    Emitted when there an issue with a configuration or
    configuration file
    """


class DBConfigWarning(ConfigurationWarning):
    """
    Emitted when there's a problem that's specific
    to the database configuration
    """


class ColumnStateChangeWarning(PyFarmWarning):
    """
    Emitted when the state column in a table changes in an unexpected
    order
    """


class CompatibilityWarning(PyFarmWarning):
    """
    Emitted when there a potential compatibility problem that
    might cause unexpected behavior.  This can usually be avoided
    by changing the configuration or using a different execution path.
    """


class NotImplementedWarning(PyFarmWarning):
    """
    Emitted when a feature is being worked on but is not yet
    implemented.  This is typically a warning for a developer to handle
    and can be safely ignored.
    """