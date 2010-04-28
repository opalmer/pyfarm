SHELL := /bin/bash
clean:
	find . -name *.pyc | xargs rm -v

snapshot:
	mkdir -p snapshots/pyfarm_`date +%F_%H-%M`_snapshot
	rsync -vrP --exclude="compiled" --exclude="logs" --exclude="*.pyc" --exclude=".*" --exclude="*.txt" --exclude="*.cmd" --exclude="*.e4p" --exclude="*.html" --exclude="Makefile" --exclude="build" --exclude="QtDesigner" --exclude="prototyping" --exclude="*.m*" --exclude="*bak*" * snapshots/pyfarm_`date +%F_%H-%M`_snapshot
	
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

compile:
	mkdir -p compiled
	python compile_pyfarm.py
	rsync -vrP --include="*.pyc" --exclude="*.py" ./lib ./compiled
	rsync -vP --include="*.pyc" --exclude="*.py" ./lib/ ./compiled
	rsync -vP --include="*.txt" --include="*.py*" --include="*.xml" --include="*.html" --include="*.cmd" --exclude="*.e4p" --exclude="compiled" --exclude="MakeFile" * ./compiled
	find lib -name *.pyc | xargs rm -v
