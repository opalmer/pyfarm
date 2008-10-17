#!/usr/bin/env python
# fetchall() - Chapter 14
# fetchall.py
# Adjust the connect() call below for your database.

import psycopg
dbh = psycopg.connect('dbname=jgoerzen user=jgoerzen')
print "Connection successful."

cur = dbh.cursor()
cur.execute("SELECT * FROM ch14")

rows = cur.fetchall()
for row in rows:
    print row
    
dbh.close()


