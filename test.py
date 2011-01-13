import os
from xml.dom import minidom

CWD      = os.path.dirname(os.path.abspath(__file__))
XML      = os.path.join(CWD, "cfg", "logger.xml")
dom      = minidom.parse(XML)
settings = dom.getElementsByTagName("Settings")[0]
globals  = settings.getElementsByTagName("globals")[0]
levels   = settings.getElementsByTagName("levels")[0]

for attr in globals.getElementsByTagName("attr"):
    print attr

for level in levels.getElementsByTagName("level"):
    print level
