# HOMEPAGE: www.pyfarm.net
# INITIAL: Oct 09 2010
# PURPOSE: To provide general tools for use during development, deployment, and
#          testing.
# 
# This file is part of PyFarm.
# Copyright (C) 2008-2010 Oliver Palmer
# 
# PyFarm is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
# 
# PyFarm is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License
# along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.

SHELL      = /bin/bash
BUILDDIR   = "build"
FROZENDIR  = $(BUILDDIR)/frozen
RELEASEDIR = $(BUILDDIR)/release

all: frozen release

clean:
	find . -name "*~" | xargs rm -fv
	find . -name "*.pyc" | xargs rm -fv

mkbuilddir:
	if test -d $(BUILDDIR); \
	then echo directory exists: $(BUILDDIR); \
	else mkdir $(BUILDDIR); \
	fi

frozen: clean mkbuilddir
	if test -d $(FROZENDIR); \
	then echo directory exists: $(FROZENDIR); \
	else mkdir $(FROZENDIR); \
	fi
	
	# setup:
	# mkdir freeze dir with naming: timestamp-repocheckout-pyfarm
	# tar directory into name above
	# gzip tar
	# delete dir

release: clean mkbuilddir
	if test -d $(RELEASEDIR); \
	then echo directory exists: $(RELEASEDIR); \
	else mkdir $(RELEASEDIR); \
	fi
	
	# setup:
	# TBD