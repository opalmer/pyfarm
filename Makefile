SHELL := /bin/bash
clean:
	find . -name *.pyc | xargs rm -v

frozen:
	rsync -vruP --exclude="*.pyc" --exclude=".*" --exclude="*.txt" --exclude="*.cmd" --exclude="*.e4p" --exclude="*.html" --exclude="Makefile" --exclude="QtDesigner" --exclude="tests" --exclude="*.m*" --exclude="*bak*" /farm/projects/PyFarm/trunk/RC3/ /farm/projects/PyFarm/trunk/Frozen
	
sfdm-update:
	rsync -vruP --exclude="*.pyc" --exclude=".*" --exclude="*.html" /home/opalme20/PyFarm/trunk/RC3 /stuhome/PyFarm/trunk
	
snapshot:
	mkdir -p /farm/projects/PyFarm/trunk/RC3/build/snapshots/pyfarm_`date +%F_%H-%M`_snapshot
	rsync -vruP --exclude="*.pyc" --exclude=".*" --exclude="*.txt" --exclude="*.cmd" --exclude="*.e4p" --exclude="*.html" --exclude="Makefile" --exclude="build" --exclude="QtDesigner" --exclude="prototyping" --exclude="*.m*" --exclude="*bak*" /farm/projects/PyFarm/trunk/RC3/ /farm/projects/PyFarm/trunk/RC3/build/snapshots/pyfarm_`date +%F_%H-%M`_snapshot
