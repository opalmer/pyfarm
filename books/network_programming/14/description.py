#!/usr/bin/env python
# metadata example - Chapter 14 - description.py
# Adjust the connect() call below for your database.

import psycopg
dbh = psycopg.connect('dbname=jgoerzen user=jgoerzen')
print "Connection successful."

cur = dbh.cursor()
cur.execute("SELECT * FROM ch14")
    
for column in cur.description:
    name, type_code, display_size, internal_size, precision, scale, null_ok = \
        column
    
    print "Column name:", name
    print "Type code:", type_code
    print "Display size:", display_size
    print "Internal size:", internal_size
    print "Precision:", precision
    print "Scale:", scale
    print "Null OK:", null_ok
    print


dbh.close()
