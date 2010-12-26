#!/usr/bin/env python

import os
msg = "You cannot run more than a single client at a time\n"
msg += "do you wish to stop the other client? ([y]/n) -> "

response = raw_input(msg) or "y"
print response
