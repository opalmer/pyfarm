#!/usr/bin/env python
# XML-RPC Basic Test Client - Chapter 17 - testclient.py

import xmlrpclib, code

url = 'http://localhost:8765/'
s = xmlrpclib.ServerProxy(url)

interp = code.InteractiveConsole({'s': s})
interp.interact("You can now use the object s to interact with the server.")
