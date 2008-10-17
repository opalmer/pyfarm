#!/usr/bin/env python
# MIME Header Parsing - Chapter 9
# mime_parse_headers.py
# This program requires Python 2.2.2 or above

import sys, email, codecs
from email import Header

msg = email.message_from_file(sys.stdin)
for header, value in msg.items():
    headerparts = Header.decode_header(value)
    headerval = []
    for part in headerparts:
        data, charset = part
	if charset is None:
	    charset = 'ascii'
	dec = codecs.getdecoder(charset)
	enc = codecs.getencoder('iso-8859-1')
	data = enc(dec(data)[0])[0]
	headerval.append(data)
    print "%s: %s" % (header, " ".join(headerval))

