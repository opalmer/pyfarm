#!/usr/bin/env python
# Commit example - Chapter 14

import psycopg

def getdsn(db = None, user = None, passwd = None, host = None):
    if user == None:
        # Default user to the one they're logged in as
        import os, pwd
        user = pwd.getpwuid(os.getuid())[0]
    if db == None:
        # Default to the username.
        db = user
    dsn = 'dbname=%s user=%s' % (db, user)
    if passwd != None:
        dsn += ' password=' + passwd
    if host != None:
        dsn += ' host=' + host
    return dsn

dsn = getdsn()
print "Connecting to %s" % dsn
dbh = psycopg.connect(dsn)
print "Connection successful."

cur = dbh.cursor()
cur.execute("DELETE FROM ch14")
cur.execute("INSERT INTO ch14 VALUES (0)")

dbh.commit()
dbh.close()


