# PURPOSE: To cleanup, build, and perform other functions on the source code.

WD=`pwd`
ARCH=`uname -m`
DOCS=$(WD)/'doc'
BUILD_DIR='build'
BUILD=$(WD)/$(BUILD_DIR)/$(ARCH)
PROGRAM='lib/ThreadedRenderQue.py'
CCPYTHON='ccPython.py'
BUILD_DOC='buildDocs.py'

clean:
	@echo "########################################################################"
	@echo "#####################Removing byte-compiled code########################"
	@echo "########################################################################"
	@find . -name '*.pyc' -type f | xargs rm -fv
	@echo "########################################################################"
	@echo "#########################Removing backup files"
	@echo "########################################################################"
	@find . -name '*~*' -type f | xargs rm -fv

build:
	@echo "########################################################################"
	@echo "#################Compiling all python code to byte-code#################"
	@echo "########################################################################"
	@python $(BUILD)/$(CCPYTHON)
	@echo "########################################################################"
	@echo "##########################Building new version##########################"
	@echo "########################################################################"
	@mkdir -p $(WD)/$(BUILD_DIR)/$(ARCH)
	@cxfreeze --target-dir=$(BUILD) $(PROGRAM)
	
doc:
	@echo "########################################################################"
	@echo "###########################Documenting Code#############################"
	@echo "########################################################################"
	@pydoc -w $(WD)/lib/ThreadedRenderQue.py
	@mkdir -p $(DOCS)/lib
	@mv -v $(WD)/ThreadedRenderQue.html $(DOCS)/lib
	
all:
	clean
	build
	doc
