#!/usr/bin/python

from lib.sequence import Sequence

s = Sequence('/home/drive', 'thisFrame', 3, 1, 50, 1, 'jpg')
seq = list(s.seq())
print seq
