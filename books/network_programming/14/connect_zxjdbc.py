#!/usr/bin/env jython
# Basic connection through zxJDBC to PostgreSQL - Chapter 14
# connect_zxjdbc.py

from com.ziclix.python.sql import zxJDBC
import os

dbh = zxJDBC.connect('jdbc:postgresql://localhost/foo',
        'jgoerzen', None, 'org.postgresql.Driver')
print "Connection successful."
dbh.close()

