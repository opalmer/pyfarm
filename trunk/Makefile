# HOMEPAGE: www.pyfarm.net
# INITIAL: Oct 09 2010
# PURPOSE: To provide general tools for use during development, deployment, and
#          testing.
#
# This file is part of PyFarm.
# Copyright (C) 2008-2011 Oliver Palmer
#
# PyFarm is free software: you can redistribute it and/or modify
# it under the terms of the GNU Lesser General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# PyFarm is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public License
# along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.

SHELL      = /bin/bash
BUILDDIR   = "dev/build"
FROZENDIR  = $(BUILDDIR)/frozen
RELEASEDIR = $(BUILDDIR)/release

all:
	@echo "Options are:"
	@echo "    clean"
	@echo "    freeze"
	@echo "    release"

clean:
	@echo "Running Clean..."
	@find . -name "*~" | xargs rm -fv
	@find . -name "*.pyc" | xargs rm -fv
	@find . -name "*.pyo" | xargs rm -fv

mkbuilddir:
	@echo "Running mkbuilddir..."
	if test -d $(BUILDDIR); \
	then echo directory exists: $(BUILDDIR); \
	else mkdir $(BUILDDIR); \
	fi

freeze: clean mkbuilddir
	@echo "Running freeze..."
	if test -d $(FROZENDIR); \
	then echo directory exists: $(FROZENDIR); \
	else mkdir $(FROZENDIR); \
	fi

release: clean mkbuilddir
	@echo "Running release..."
	if test -d $(RELEASEDIR); \
	then echo directory exists: $(RELEASEDIR); \
	else mkdir $(RELEASEDIR); \
	fi
