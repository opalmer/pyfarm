#!/usr/bin/env python
# executemany() example - Chapter 14 - executemany.py

import psycopg

rows = ({'num': 0, 'text': 'Zero'},
        {'num': 1, 'text': 'Item One'},
        {'num': 2, 'text': 'Item Two'},
        {'num': 3, 'text': 'Three'})

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
cur.executemany("INSERT INTO ch14 VALUES (%(num)d, %(text)s)", rows)
dbh.commit()
dbh.close()


