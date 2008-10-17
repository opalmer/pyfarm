#!/usr/bin/env python
# fetchmany() - Chapter 14 - fetchmany.py
# Adjust the connect() call below for your database.

import psycopg
dbh = psycopg.connect('dbname=jgoerzen user=jgoerzen')
print "Connection successful."

cur = dbh.cursor()
cur.execute("SELECT * FROM ch14")
cur.arraysize = 2

while 1:
    rows = cur.fetchmany()
    print "Obtained %d results from fetchmany()." % len(rows)
    if not len(rows):
        break

    for row in rows:
        print row
    
dbh.close()
