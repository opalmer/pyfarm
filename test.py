from xml.dom import minidom
from lib import Logger

log = Logger.Logger('test.py')
for level in log.levelCalls:
    eval('log.%s("test")' % level)
