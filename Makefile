all:
	cxfreeze --include-modules=sip --install-dir=build/binaries/ubuntu_8.1_x86_64/Client Client.py
	cxfreeze --include-modules=sip --install-dir=build/binaries/ubuntu_8.1_x86_64/Main Main.pyw
	mkdir -p build/source
	cp -v settings.cfg build/binaries/ubuntu_8.1_x86_64/Client/
	cp -v settings.cfg build/binaries/ubuntu_8.1_x86_64/Main/
	cp -Rfv Client.py Main.pyw settings.cfg lib/ build/source
