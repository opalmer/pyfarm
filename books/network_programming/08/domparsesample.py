#!/usr/bin/env python
# Parsing Sample with DOM - Chapter 8 - domparsesample.py
# This program requires Python 2.3 for the textwrap module

from xml.dom import minidom, Node
import re, textwrap

class SampleScanner:
    def __init__(self, doc):
        for child in doc.childNodes:
            if child.nodeType == Node.ELEMENT_NODE and \
                   child.tagName == 'book':
                self.handleBook(child)

    def gettext(self, nodelist):
        """Given a list of one or more nodes, recursively finds all text
        nodes in that list (or children of nodes in that list), concatenates
        them, removes duplicate spaces, and returns the result."""
        retlist = []
        for node in nodelist:
            if node.nodeType == Node.TEXT_NODE:
                retlist.append(node.wholeText)
            elif node.hasChildNodes:
                retlist.append(self.gettext(node.childNodes))

        return re.sub('\s+', ' ', ''.join(retlist))

    def handleBook(self, node):
        """Process the book tag.  Look for title, author, then chapters."""

        for child in node.childNodes:
            if child.nodeType != Node.ELEMENT_NODE:
                continue
            if child.tagName == 'title':
                print "Book title is:", self.gettext(child.childNodes)
            if child.tagName == 'author':
                self.handleAuthor(child)
            if child.tagName == 'chapter':
                self.handleChapter(child)

    def handleAuthor(self, node):
        for child in node.childNodes:
            if child.nodeType != Node.ELEMENT_NODE:
                continue
            if child.tagName == 'name':
                self.handleAuthorName(child)
            elif child.tagName == 'affiliation':
                print "Author affiliation:", self.gettext([child])

    def handleAuthorName(self, node):
        surname = self.gettext(node.getElementsByTagName("last"))
        givenname = self.gettext(node.getElementsByTagName("first"))
        print "Author Name: %s, %s" % (surname, givenname)

    def handleChapter(self, node):
        print " *** Start of Chapter %s: %s" % \
            (node.getAttribute('number'),
             self.gettext(node.getElementsByTagName('title')))
        for child in node.childNodes:
            if child.nodeType != Node.ELEMENT_NODE:
                continue
            if child.tagName == 'para':
                self.handlePara(child)

    def handlePara(self, node):
        partext = self.gettext([node])
        partext = textwrap.fill(partext)
        print partext
        print

doc = minidom.parse('sample.xml') 
SampleScanner(doc)

