SHELL := /bin/bash
LICENSE   = docs/license
UILIB     = lib/ui

clean:
	echo "NOT IMPLIMENTED: Use Main.pyw --clean"

gui:
	cat ${LICENSE}/GNU-GPL_Header.txt > ${UILIB}/MainWindow.py
	pyuic4 QtDesigner/MainWindow.ui >> ${UILIB}/MainWindow.py
	cat ${LICENSE}/GNU-GPL_Header.txt > ${UILIB}/HostInfo.py
	pyuic4 QtDesigner/HostInfo.ui >> ${UILIB}/HostInfo.py
	cat ${LICENSE}/GNU-GPL_Header.txt > ${UILIB}/JobDetails.py
	pyuic4 QtDesigner/JobDetails.ui >> ${UILIB}/JobDetails.py
	cat ${LICENSE}/GNU-GPL_Header.txt > ${UILIB}/LogViewer.py
	pyuic4 QtDesigner/LogViewer.ui >> ${UILIB}/LogViewer.py
	cat ${LICENSE}/GNU-GPL_Header.txt > ${UILIB}/NewTicket.py
	pyuic4 QtDesigner/NewTicket.ui >> ${UILIB}/NewTicket.py
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
