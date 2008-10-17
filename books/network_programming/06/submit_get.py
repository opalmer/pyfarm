#!/usr/bin/env python
# Submit GET Data - Chapter 6 - submit_get.py
import sys, urllib2, urllib

def addGETdata(url, data):
    """Adds data to url.  Data should be a list or tuple consisting of 2-item
    lists or tuples of the form: (key, value).

    Items that have no key should have key set to None.

    A given key may occur more than once.
    """
    return url + '?' + urllib.urlencode(data)

zipcode = sys.argv[1]
url = addGETdata('http://www.wunderground.com/cgi-bin/findweather/getForecast',
                 [('query', zipcode)])
print "Using URL", url
req = urllib2.Request(url)
fd = urllib2.urlopen(req)
while 1:
    data = fd.read(1024)
    if not len(data):
        break
    sys.stdout.write(data)
