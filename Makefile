SHELL := /bin/bash
clean:
	find . -name *.pyc | xargs rm -v

frozen:
	rsync -vruP --exclude="*.pyc" --exclude=".*" --exclude="*.txt" --exclude="*.cmd" --exclude="*.e4p" --exclude="*.html" --exclude="Makefile" --exclude="QtDesigner" --exclude="tests" --exclude="*.m*" --exclude="*bak*" /farm/projects/PyFarm/trunk/RC3/ /farm/projects/PyFarm/trunk/Frozen
	
sfdm-update:
	rm -Rfv /stuhome/PyFarm/trunk/*
	rsync -vruP --perms --exclude="*.pyc" --exclude=".*" --exclude="*.html" --exclude="Makefile" --exclude="*.e4p" --exclude="*.txt" --exclude="QtDesigner" --exclude="*.cmd" /home/opalme20/PyFarm/trunk/v0.3 /stuhome/PyFarm/trunk

	
snapshot:
	mkdir -p /farm/projects/PyFarm/trunk/RC3/build/snapshots/pyfarm_`date +%F_%H-%M`_snapshot
	rsync -vruP --exclude="*.pyc" --exclude=".*" --exclude="*.txt" --exclude="*.cmd" --exclude="*.e4p" --exclude="*.html" --exclude="Makefile" --exclude="build" --exclude="QtDesigner" --exclude="prototyping" --exclude="*.m*" --exclude="*bak*" /farm/projects/PyFarm/trunk/RC3/ /farm/projects/PyFarm/trunk/RC3/build/snapshots/pyfarm_`date +%F_%H-%M`_snapshot
	
gui:
	cat GNU-GPL_Header.txt > lib/ui/RC3.py
	cat GNU-GPL_Header.txt > lib/ui/HostInfo.py
	cat GNU-GPL_Header.txt > lib/ui/JobDetails.py
	cat GNU-GPL_Header.txt > lib/ui/LogViewer.py
	cat GNU-GPL_Header.txt > lib/ui/WikiDocGen.py

	pyuic4 QtDesigner/RC3.ui >> lib/ui/RC3.py
	pyuic4 QtDesigner/HostInfo.ui >> lib/ui/HostInfo.py
	pyuic4 QtDesigner/JobDetails.ui >> lib/ui/JobDetails.py
	pyuic4 QtDesigner/WikiDocGen.ui >> lib/ui/WikiDocGen.py
	
build-mac:
	find . -name *.pyc | xargs rm -v
	python build/mac/setup.py py2app
	rsync -vruP --exclude="*.pyc" --exclude=".*" /farm/projects/PyFarm/trunk/RC3/lib /farm/projects/PyFarm/trunk/RC3/build/mac/Main.app/Contents/Resources 
	cp -Rfv /farm/projects/PyFarm/trunk/RC3/settings.xml /farm/projects/PyFarm/trunk/RC3/build/mac/Main.app/Contents/Resources
