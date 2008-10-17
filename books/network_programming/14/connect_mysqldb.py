#!/usr/bin/env python
# Basic connection to MySQL with mysqldb - Chapter 14
# connect_mysqldb.py

import MySQLdb

print "Connecting..."
dbh = MySQLdb.connect(db = "foo")
print "Connection successful."
dbh.close()

