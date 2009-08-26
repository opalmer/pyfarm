SHELL := /bin/bash
clean:
	find . -name *.pyc | xargs rm -v

frozen:
	rsync -vruP --exclude="*.pyc" --exclude=".*" --exclude="*.txt" --exclude="*.cmd" --exclude="*.e4p" --exclude="*.html" --exclude="Makefile" --exclude="QtDesigner" --exclude="tests" --exclude="*.m*" --exclude="*bak*" /farm/projects/PyFarm/trunk/RC3/ /farm/projects/PyFarm/trunk/Frozen

snapshot:
	mkdir -p /farm/projects/PyFarm/trunk/RC3/build/snapshots/pyfarm_`date +%F_%H-%M`_snapshot
	rsync -vruP --exclude="*.pyc" --exclude=".*" --exclude="*.txt" --exclude="*.cmd" --exclude="*.e4p" --exclude="*.html" --exclude="Makefile" --exclude="build" --exclude="QtDesigner" --exclude="prototyping" --exclude="*.m*" --exclude="*bak*" /farm/projects/PyFarm/trunk/RC3/ /farm/projects/PyFarm/trunk/RC3/build/snapshots/pyfarm_`date +%F_%H-%M`_snapshot
	
gui:
	cat GNU-GPL_Header.txt > lib/ui/MainWindow.py
	pyuic4 QtDesigner/MainWindow.ui >> lib/ui/MainWindow.py
	cat GNU-GPL_Header.txt > lib/ui/HostInfo.py
	pyuic4 QtDesigner/HostInfo.ui >> lib/ui/HostInfo.py
	cat GNU-GPL_Header.txt > lib/ui/JobDetails.py
	pyuic4 QtDesigner/JobDetails.ui >> lib/ui/JobDetails.py
	cat GNU-GPL_Header.txt > lib/ui/LogViewer.py
	pyuic4 QtDesigner/LogViewer.ui >> lib/ui/LogViewer.py
	cat GNU-GPL_Header.txt > doc/docgen/lib/ui/WikiDocGen.py
	pyuic4 doc/docgen/QtDesigner/WikiDocGen.ui >> doc/docgen/lib/ui/WikiDocGen.py
	cat GNU-GPL_Header.txt > doc/docgen/lib/ui/NewTicket.py
	pyuic4 QtDesigner/NewTicket.ui >> lib/ui/NewTicket.py

build-mac:
	find . -name *.pyc | xargs rm -v
	python build/mac/setup.py py2app
	rsync -vruP --exclude="*.pyc" --exclude=".*" /farm/projects/PyFarm/trunk/RC3/lib /farm/projects/PyFarm/trunk/RC3/build/mac/Main.app/Contents/Resources 
	cp -Rfv /farm/projects/PyFarm/trunk/RC3/settings.xml /farm/projects/PyFarm/trunk/RC3/build/mac/Main.app/Contents/Resources
