#!/usr/bin/env python
# Error-checking gethostbyaddr() example - Chapter 4
# gethostbyaddr-paranoid.py
# Performs a reverse lookup on the IP address given on the command line
# and sanity-checks the result.

import sys, socket

def getipaddrs(hostname):
    """Get a list of IP addresses from a given hostname.  This is a standard
    (forward) lookup."""
    result = socket.getaddrinfo(hostname, None, 0, socket.SOCK_STREAM)
    return [x[4][0] for x in result]

def gethostname(ipaddr):
    """Get the hostname from a given IP address.  This is a reverse
    lookup."""
    return socket.gethostbyaddr(ipaddr)[0]

try:
    # First, do the reverse lookup and get the hostname.
    hostname = gethostname(sys.argv[1]) # could raise socket.herror

    # Now, do a forward lookup on the result from the earlier reverse
    # lookup.
    ipaddrs = getipaddrs(hostname)      # could raise socket.gaierror
except socket.herror, e:
    print "No host names available for %s; this may be normal." % sys.argv[1]
    sys.exit(0)
except socket.gaierror, e:
    print "Got hostname %s, but it could not be forward-resolved: %s" % \
          (hostname, str(e))
    sys.exit(1)

# If the forward lookup did not yield the original IP address anywhere,
# someone is playing tricks.  Explain the situation and exit.
if not sys.argv[1] in ipaddrs:
    print "Got hostname %s, but on forward lookup," % hostname
    print "original IP %s did not appear in IP address list." % sys.argv[1]
    sys.exit(1)

# Otherwise, show the validated hostname.
print "Validated hostname:", hostname
