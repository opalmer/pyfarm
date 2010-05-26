'''
HOMEPAGE: www.pyfarm.net
INITIAL: May 19 2010
PURPOSE: To perform general operations such as setup on a sqlite database

    This file is part of PyFarm.
    Copyright (C) 2008-2010 Oliver Palmer

    PyFarm is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

PyFarm is distributed in the hopehttp://docs.python.org/library/sqlite3.html that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.
'''

import sqlite3

def DBSetup(db=":memory:"):
    '''Setup the database and the default tables'''
    sql = sqlite3.connect(db)
    db = sql.cursor()
    db.executescript("""
        CREATE TABLE IF NOT EXISTS hosts(
            uuid text,
            hostname text,
            ip text,
            mac text,
            cpu_count int,
            frames_max int,
            frames_rendering int,
            ram_total int,
            ram_used int,
            load real
        );

        CREATE TABLE IF NOT EXISTS frames(
            frame int,
            job text,
            subjob text,
            software_required text,
            time_start real,
            time_end real
        );

        CREATE TABLE IF NOT EXISTS settings(
            port_server_status int,
            port_server_queue int
        );
        """)
    sql.commit()
    db.close()
    return db

def DBDump(db, location):
    '''Dump the given database to a location'''
    pass

if __name__ != "__MAIN__":
    log = Logger("DBGeneral.Test")
    log.debug("Running DBGeneral Demo.")
    log.debug("Creating database")
    db = DBSetup()
    log.debug("done")