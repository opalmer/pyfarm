'''
HOMEPAGE: www.pyfarm.net
INITIAL: Sept 26 2009
PURPOSE: Basic variable holder used to give structure to database tables

    This file is part of PyFarm.
    Copyright (C) 2008-2009 Oliver Palmer

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

# the below values control the structure of the SQLite Tables, mod at your own risk
TABLES = {"stats":("hostCount", "hostsActive",
                                "jobCount", "jobActive",
                                "framesCount", "framesComplete", "framesRendering", "framesFailed"
                                ),
                  "software":("widgetId", "commonName", "common"),
                  "network":("hostname", "ip", "status"),
                  "frames":("job", "subjob", "fnum", "sTime", "eTime", "status", "command", "logid"),
                  "logs":("id", "host","exitCode", "stdout", "stderr"),
                }
