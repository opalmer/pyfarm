#!/usr/bin/env python
# XML-RPC Basic Client - Chapter 8 - xmlrpcbasic.py

import xmlrpclib
url = 'http://www.oreillynet.com/meerkat/xml-rpc/server.php'
s = xmlrpclib.ServerProxy(url)
catdata = s.meerkat.getCategories()
cattitles = [item['title'] for item in catdata]
cattitles.sort()
for item in cattitles:
    print item

