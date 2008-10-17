#!/usr/bin/env python
# HTML Title Retriever With Entity Support - Chapter 7 - etitle.py

from htmlentitydefs import entitydefs
from HTMLParser import HTMLParser
import sys

class TitleParser(HTMLParser):
    def __init__(self):
        self.title = ''
        self.readingtitle = 0
        HTMLParser.__init__(self)

    def handle_starttag(self, tag, attrs):
        if tag == 'title':
            self.readingtitle = 1

    def handle_data(self, data):
        if self.readingtitle:
            self.title += data

    def handle_endtag(self, tag):
        if tag == 'title':
            self.readingtitle = 0

    def handle_entityref(self, name):
        if entitydefs.has_key(name):
            self.handle_data(entitydefs[name])
        else:
            self.handle_data('&' + name + ';')

    def gettitle(self):
        return self.title

fd = open(sys.argv[1])
tp = TitleParser()
tp.feed(fd.read())
print "Title is:", tp.gettitle()
