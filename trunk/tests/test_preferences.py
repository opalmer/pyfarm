#!/usr/bin/env python
#
# This file is part of PyFarm.
# Copyright (C) 2008-2012 Oliver Palmer
#
# PyFarm is free software: you can redistribute it and/or modify
# it under the terms of the GNU Lesser General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# PyFarm is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public License
# along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.

import os
import nose
import uuid
import string

import setuptests

from pyfarm import fileio, PYFARM_ETC, PYFARM_ROOT
from pyfarm.datatypes.system import OSNAME
from pyfarm import preferences as p

SPECIAL_PREFS_KEYS = (
    'jobtypes.path', 'jobtypes.search-paths', 'jobtypes.extensions',
    'client.max-jobs',
)

def test_prefssetup():
    assert hasattr(p, 'prefs')
    assert isinstance(p.prefs, p.Preferences)
    assert not p.prefs.loaded
    assert not p.prefs.data
# test_hasprefs

def test_globaletc():
    assert os.path.isdir(PYFARM_ETC)
# end test_globaletc

def test_basicget():
    for name in os.listdir(PYFARM_ETC):
        if not name.endswith(".template"):
            path = os.path.join(PYFARM_ETC, name)
            filename, extension = os.path.splitext(name)
            data = fileio.yml.load(path)

            for key in data.iterkeys():
                prefs_key = "%s.%s" % (filename, key)
                prefs_data = p.prefs.get(prefs_key)
                file_data = data[key]

                if isinstance(prefs_data, dict):
                    assert tuple(file_data.iteritems()) == tuple(prefs_data.iteritems())

                elif prefs_key not in SPECIAL_PREFS_KEYS:
                    assert file_data == prefs_data
# test_basicget

def test_jobtypes_path():
    template_vars = {
        "root" : p.prefs.get('filesystem.roots.%s' % OSNAME)
    }
    pref_paths = p.prefs.get('jobtypes.path')
    file_paths = p.prefs.data['jobtypes']['path']

    paths = []
    for value in [os.path.expandvars(value) for value in file_paths]:
        if os.pathsep in value:
            for entry in value.split(os.pathsep):
                paths.append(entry)
        else:
            paths.append(value)

    results = []
    for path in paths:
        template = string.Template(path)

        path = template.safe_substitute(template_vars)
        if os.path.isdir(path) and path not in results:
            results.append(path)

    assert pref_paths == results
# end test_jobtypes_path

def test_db_envconfig():
    value = str(uuid.uuid4())
    os.environ['PYFARM_DB_CONFIG'] = value
    assert p.prefs.get('database.setup.config') == value
    del os.environ['PYFARM_DB_CONFIG']
# end test_db_envconfig

def test_dburl():
    db = p.prefs.get('database.setup.config')
    prefs_config = p.prefs.get('database.%s' % db)
    data = p.prefs.data['database'][db]
    
    # retrieve the settings from the config
    driver = data.get('driver')
    engine = data.get('engine')
    dbname = data.get('name')
    dbuser = data.get('user')
    dbpass = data.get('pass')
    dbhost = data.get('host')
    dbport = data.get('dbport')

    # configure the url
    data_url = engine

    # adds the driver if it was found in the preferences
    if driver:
        data_url += "+%s" % driver

    # the start of the url changes slightly for sqlite connections
    data_url += "://"

    # setup the username, password, host, and port
    data_url += "%s:%s@%s" % (dbuser, dbpass, dbhost)
    if isinstance(dbport, int):
        data_url += ":%i" % dbport

    data_url += "/%s" % dbname


# end test_dburl

if __name__ == '__main__':
    nose.runmodule()
