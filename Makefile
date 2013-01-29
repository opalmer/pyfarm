DB_ECHO ?= True
DB_REBUILD ?= True
PYTHON ?= python
PYTHONPATH ?= lib/python
CONFIG_DIR ?= etc
CONFIG_FILES ?= $(wildcard $(CONFIG_DIR)/*.yml.template)

shell:
	@env PYTHONPATH=$(PYTHONPATH) ipython

clean:
	@find . -name "*.pyc" -exec rm -f {} \;
	@find . -name "*.pyo" -exec rm -f {} \;
	@find . -name "*~" -exec rm -f {} \;

jobs: dev.make_jobs

tables:
	@echo "rebuilding tables"
	@env PYTHONPATH=$(PYTHONPATH) $(PYTHON) -c 'from pyfarm.db import tables; tables.init(rebuild=True)'

db: tables dev.make_jobs
	@echo "rebuilding database"

configs:
	@$(foreach filename, $(CONFIG_FILES), \
		$(MAKE) -s config.$(subst .template,,$(subst $(CONFIG_DIR)/,,$(filename))); \
	)

config.%: TARGET=$(subst .yml,,$(subst config.,,$@))
config.%: SOURCE=$(CONFIG_DIR)/$(TARGET).yml.template
config.%: DEST=$(CONFIG_DIR)/$(TARGET).yml 
config.%:
	@if test ! -e $(DEST); \
        then echo "creating configuration $(DEST)"; \
            cp $(SOURCE) $(DEST); \
	fi

bin.%: SCRIPT=bin/pyf$(subst bin.,,$@)
bin.%:
	@echo "running $(SCRIPT)"
	@$(PYTHON) $(SCRIPT)

dev.%: SCRIPT=dev/bin/$(subst dev.,,$@).py
dev.%:
	@echo "running $(SCRIPT)"
	@env PYTHONPATH=$(PYTHONPATH) $(PYTHON) $(SCRIPT)

test.%: SCRIPT=tests/test_$(subst test.,,$@).py
test.%:
	@echo "running $(SCRIPT)"
	@env PYTHONPATH=$(PYTHONPATH) $(PYTHON) $(SCRIPT)

setup.%: TARGET=$(subst setup.,,$@)
setup.%:
	python setup.py $(TARGET)

docs.%: TARGET=$(subst docs.,,$@)
docs.%: clean setup.install
	$(MAKE) -s -C docs $(TARGET)

tests: test.preferences
