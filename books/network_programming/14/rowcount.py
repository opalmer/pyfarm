#!/usr/bin/env python
# rowcount example - Chapter 14 - rowcount.py
# Adjust the connect() call below for your database.

import psycopg
dbh = psycopg.connect('dbname=jgoerzen user=jgoerzen')
print "Connection successful."

cur = dbh.cursor()
cur.execute("SELECT * FROM ch14")
cur.fetchone()
    
print "Obtained %d rows" % cur.rowcount

dbh.close()
