SHELL := /bin/bash
clean:
	echo "NOT IMPLIMENTED: Use Main.pyw --clean"

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
	cat GNU-GPL_Header.txt > lib/ui/NewTicket.py
	pyuic4 QtDesigner/NewTicket.ui >> lib/ui/NewTicket.py

compile:
	echo "NOT IMPLIMENTED: make compile"
	mkdir -p compiled
	echo "FIXME: Main.pyw --compile > not fully written yet"
	python Main.pyw --compile
	echo "FIXME: Main.pyw --compile > proper logging not implimented"
