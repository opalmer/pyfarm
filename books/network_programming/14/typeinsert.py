#!/usr/bin/env python
# Using types to insert data - Chapter 14 - typeinsert.py
# Adjust the connect() call below for your database.

import psycopg, time

dsn = 'dbname=jgoerzen user=jgoerzen' 
print "Connecting to %s" % dsn
dbh = psycopg.connect(dsn)
print "Connection successful."

cur = dbh.cursor()
cur.execute("""CREATE TABLE ch14types (
        mydate    DATE,
        mytimestamp TIMESTAMP,
        mytime  TIME,
        mystring varchar(30))""")
query = """INSERT INTO ch14types VALUES (
    %(mydate)s, %(mytimestamp)s, %(mytime)s, %(mystring)s)"""
rows = ( \
        {'mydate': psycopg.Date(2000, 12, 25),
         'mytimestamp': psycopg.Timestamp(2000, 12, 15, 06, 30, 00),
         'mytime': psycopg.Time(6, 30, 00),
         'mystring': 'Christmas - Wake Up!'},
        {'mydate': psycopg.DateFromTicks(time.time()),
         'mytime': psycopg.TimeFromTicks(time.time()),
         'mytimestamp': psycopg.TimestampFromTicks(time.time()),
         'mystring': None})
cur.executemany(query, rows)
dbh.commit()
dbh.close()
