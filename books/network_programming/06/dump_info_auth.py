#!/usr/bin/env python
# Obtain Web Page Information With Authentication - Chapter 6
# dump_info_auth.py
import sys, urllib2, getpass

class TerminalPassword(urllib2.HTTPPasswordMgr):
    def find_user_password(self, realm, authuri):
        retval = urllib2.HTTPPasswordMgr.find_user_password(self, realm,
                                                            authuri)
        if retval[0] == None and retval[1] == None:
            # Did not find it in stored values; prompt user.
            sys.stdout.write("Login required for %s at %s\n" % \
                             (realm, authuri))
            sys.stdout.write("Username: ")
            username = sys.stdin.readline().rstrip()
            password = getpass.getpass().rstrip()
            return (username, password)
        else:
            return retval

req = urllib2.Request(sys.argv[1])
opener = urllib2.build_opener(urllib2.HTTPBasicAuthHandler(TerminalPassword()))
fd = opener.open(req)
print "Retrieved", fd.geturl()
info = fd.info()
for key, value in info.items():
    print "%s = %s" % (key, value)
    
