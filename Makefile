SHELL := /bin/bash
clean:
	echo "NOT IMPLIMENTED: Use Main.pyw --clean"

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
	echo "FIXME: Transition gui complie to Main.pyw --make-gui"

compile:
	echo "NOT IMPLIMENTED: make compile"
	mkdir -p compiled
	echo "FIXME: Main.pyw --compile > not fully written yet"
	python Main.pyw --compile
	echo "FIXME: Main.pyw --compile > proper logging not implimented"

profile:
	rm -f logs/profile.log
	mkdir -p logs
	python -m cProfile Main.pyw > logs/profile.log

testing:
	rsync -ruvph ~/pyfarm /farm/ --exclude=*.git* --exclude=*.pyc --exclude=QtDesigner* --exclude=.eric* --exclude=*log* --exclude=*.txt --exclude=*GNU*