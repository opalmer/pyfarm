#!/usr/bin/env python
# fetchone() with dictionary -- Chapter 15 
# Adjust the connect() call below for your database.

import psycopg

def dictfetchone(cur):
    seq = cur.fetchone()
    if seq == None:
        return seq
    result = {}
    colnum = 0
    for column in cur.description:
        result[column[0]] = seq[colnum]
        colnum += 1
    return result

dbh = psycopg.connect('dbname=jgoerzen user=jgoerzen')
print "Connection successful."

cur = dbh.cursor()
cur.execute("SELECT * FROM ch14")
while 1:
    row = dictfetchone(cur)
    if row == None:
        break
    print row
dbh.close()
