#!/usr/bin/python

import lib.FarmLog as log

log.setup(name='TCPServer')
log.level('info')
log.info('Something happened')