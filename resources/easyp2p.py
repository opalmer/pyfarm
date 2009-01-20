#!/usr/bin/python

# simplep2p.py v1.1 (C) 2004, Richard Jones
# with litle touch from Leonardo Santagada
# A slightly more readable version of tinyp2p.py (C) 2004, E.W. Felten
# (also handles binary transmissions now too)
# license: http://creativecommons.org/licenses/by-nc-sa/2.0
# Usage:
#   python simplep2p.py password server hostname portnum [otherurl]
#   python simplep2p.py 1234 server 10.56.1.243 2200 http://10.56.1.240:2240 <<< - full usage example
# or
#   python simplep2p.py password client serverurl pattern
import os, SimpleXMLRPCServer, re, hmac, sets, base64
from sys import argv
from xmlrpclib import ServerProxy

def gen_password(url):
   return hmac.new(argv[1], url).hexdigest()
def ls(pat=""):
    ''' List the files in the current working directory that optionall match
    a regular expression "pat". '''
    return [fn for fn in os.listdir(os.getcwd())
        if not pat or re.search(pat, fn)]

if argv[2] == "server":
    my_url = "http://"+argv[3]+":"+argv[4]

    # keep a list of servers we know about
    servers = sets.Set([my_url] + argv[5:])
    def update_servers(new_servers=[]):
        servers.union_update(sets.Set(new_servers))
        return list(servers)
    def discover(other_url):
        if other_url == my_url: return servers
        pw = gen_password(other_url)
        server = ServerProxy(other_url)
        return update_servers(server.list_servers(pw, update_servers()))

    # ask all our known servers about the servers *they* know about
    if servers: [discover(url) for url in list(servers)]

    # serve up the files
    def list_servers(password, arg=[]):
        if password == gen_password(my_url):
            return update_servers(arg)
    def list_files(password, arg):
        if password == gen_password(my_url):
            return ls(arg)
    def get_file(password, arg):
        if password == gen_password(my_url):
            f = file(arg)
            try:
                return base64.encodestring(f.read())
            finally:
                f.close()
    server = SimpleXMLRPCServer.SimpleXMLRPCServer((argv[3], int(argv[4])))
    server.register_function(list_servers)
    server.register_function(list_files)
    server.register_function(get_file)
    server.serve_forever()

# client - contact our server
for url in ServerProxy(argv[3]).list_servers(gen_password(argv[3])):
    # ask for the files we want, that we don't already have
    files = sets.Set(ServerProxy(url).list_files(gen_password(url), argv[4]))
    my_files = sets.Set(ls())
    for fn in files - my_files:
    	print fn
        # and fetch
        c = ServerProxy(url).get_file(gen_password(url), fn)
        f = file(fn, "wb")
        f.write(base64.decodestring(c))
        f.close()
