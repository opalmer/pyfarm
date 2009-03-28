clean:
	find . -name *.pyc | xargs rm -v

build:
	cxfreeze -OO --include-modules=sip --install-dir=build/binaries/ubuntu_8.1_x86_64/Client Client.py
	cxfreeze -OO --include-modules=sip --install-dir=build/binaries/ubuntu_8.1_x86_64/Main Main.pyw
	mkdir -vp build/source
	cp -Rfuv settings.cfg GNU-GPL_License_v3.html Main.pyw Client.py build/source
	rsync -vruP --exclude="*.pyc" --exclude="old" lib/ build/source/lib/

frozen:
	rsync -vruP --exclude="*.pyc" --exclude=".*" --exclude="*.txt" --exclude="*.cmd" --exclude="*.e4p" --exclude="*.html" --exclude="Makefile" --exclude="QtDesigner" --exclude="tests" --exclude="*.m*" --exclude="*bak*" /farm/projects/PyFarm/trunk/RC3/ /farm/projects/PyFarm/trunk/Frozen
	
sfdm-update:
	rsync -vruP --exclude="*.pyc" --exclude=".*" --exclude="*.html" /home/opalme20/PyFarm/trunk/RC3 /stuhome/PyFarm/trunk

linecount:
	cat `find . -name '*.py*'` | wc -l
