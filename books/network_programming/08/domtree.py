#!/usr/bin/env python
# Tree Generation with DOM - Chapter 8 - domtree.py

from xml.dom import minidom, Node

def scanNode(node, level = 0):
    msg = node.__class__.__name__
    if node.nodeType == Node.ELEMENT_NODE:
        msg += ", tag: " + node.tagName
    print " " * level * 4, msg
    if node.hasChildNodes:
        for child in node.childNodes:
            scanNode(child, level + 1)
            
doc = minidom.parse('sample.xml') 
scanNode(doc)

