#!/usr/bin/env python
# fetchone() - Chapter 14 - fetchone.py
# Adjust the connect() call below for your database.

import psycopg
dbh = psycopg.connect('dbname=jgoerzen user=jgoerzen')
print "Connection successful."

cur = dbh.cursor()
cur.execute("SELECT * FROM ch14")
cur.arraysize = 2

while 1:
    row = cur.fetchone()
    if row is None:
        break
    print row
    
dbh.close()
