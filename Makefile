SHELL := /bin/bash
LICENSE   = docs/license
UILIB     = lib/ui

clean:
	echo "NOT IMPLIMENTED: Use Main.pyw --clean"

profile:
	rm -f logs/profile.log
	mkdir -p logs
	python -m cProfile Main.pyw > logs/profile.log

testing:
	rsync -ruvph ~/pyfarm /farm/ --exclude=*.git* --exclude=*.pyc --exclude=QtDesigner* --exclude=.eric* --exclude=*log* --exclude=*.txt --exclude=*GNU*
