#!/usr/bin/python
'''
HOMEPAGE: www.pyfarm.net
INITIAL: Oct 07 2010
PURPOSE: Ensure all modules will import properly and are included in the current installation

    This file is part of PyFarm.
    Copyright (C) 2008-2010 Oliver Palmer

    PyFarm is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    PyFarm is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.
'''
import os
import sys
import unittest

rootDir = os.path.abspath(__file__)
for i in range(3): rootDir = os.path.dirname(rootDir)
if rootDir not in sys.path: sys.path.append(rootDir)

class ModuleTests(unittest.TestCase):
    def setUp(self):
       pass

if __name__ == "__main__":
    unittest.main()