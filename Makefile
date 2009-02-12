build:
	cxfreeze -OO --include-modules=sip --install-dir=build/binaries/ubuntu_8.1_x86_64/Client Client.py
	cxfreeze -OO --include-modules=sip --install-dir=build/binaries/ubuntu_8.1_x86_64/Main Main.pyw
	mkdir -vp build/source
	cp -Rfuv settings.cfg GNU-GPL_License_v3.html Main.pyw Client.py build/source
	rsync -vruP --exclude="*.pyc" --exclude="old" lib/ build/source/lib/
	
freeze:
	cp -fvu /farm/projects/PyFarm/trunk/RC2/*.py /farm/projects/PyFarm/trunk/Frozen
	cp -fvu /farm/projects/PyFarm/trunk/RC2/*.pyw /farm/projects/PyFarm/trunk/Frozen
	cp -fvu /farm/projects/PyFarm/trunk/RC2/settings.cfg /farm/projects/PyFarm/trunk/Frozen
	cp -fvu /farm/projects/PyFarm/trunk/RC2/lib/*.py /farm/projects/PyFarm/trunk/Frozen/lib
	cp -fvu /farm/projects/PyFarm/trunk/RC2/lib/ui/*.py /farm/projects/PyFarm/trunk/Frozen/lib/ui
	
clean:
	rm -Rfv lib/*.pyc
