all:
	cxfreeze -OO --include-modules=sip --install-dir=build/binaries/ubuntu_8.1_x86_64/Client Client.py
	cxfreeze -OO --include-modules=sip --install-dir=build/binaries/ubuntu_8.1_x86_64/Main Main.pyw
	mkdir -vp build/source
	cp -Rfuv settings.cfg GNU-GPL_License_v3.html Main.pyw Client.py build/source
	rsync -vruP --exclude="*.pyc" --exclude="old" lib/ build/source/lib/
	
clean:
	rm -Rfv lib/*.pyc
