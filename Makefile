DB_ECHO = True
DB_REBUILD = True
PYTHON = python
PYTHONPATH = lib/python

shell:
	@env PYTHONPATH=$(PYTHONPATH) ipython

clean:
	@find . -name "*.pyc" -exec rm {} \;
	@find . -name "*.pyo" -exec rm {} \;
	@find . -name "*~" -exec rm {} \;

jobs: dev.make_jobs

tables:
	@echo "rebuilding tables"
	@env PYTHONPATH=$(PYTHONPATH) $(PYTHON) -c 'from pyfarm.db import tables; tables.init(rebuild=True)'

db: tables dev.make_jobs
	@echo "rebuilding database"

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

tests: test.preferences
