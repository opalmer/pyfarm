#!/usr/bin/env python
# Submit POST Data - Chapter 6 - submit_post.py
import sys, urllib2, urllib

zipcode = sys.argv[1]
url = 'http://www.wunderground.com/cgi-bin/findweather/getForecast'
data = urllib.urlencode([('query', zipcode)])
req = urllib2.Request(url)
fd = urllib2.urlopen(req, data)
while 1:
    data = fd.read(1024)
    if not len(data):
        break
    sys.stdout.write(data)
