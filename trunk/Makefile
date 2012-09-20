DB_ECHO = True
DB_REBUILD = True
PYTHON = python
PYTHONPATH = lib/python

shell:
	@env PYTHONPATH=$(PYTHONPATH) ipython

clean:
	@echo "running clean"
	@find . -name "*.pyc" -exec rm {} \;
	@find . -name "*.pyo" -exec rm {} \;
	@find . -name "*~" -exec rm {} \;

jobs:
	@echo "adding random job data"
	@env PYTHONPATH=$(PYTHONPATH) $(PYTHON) ../dev/bin/make_jobs.py

tables:
	@echo "rebuilding tables"
	@env PYTHONPATH=$(PYTHONPATH) $(PYTHON) -c 'from pyfarm.db import tables; tables.init(rebuild=True)'

db: tables jobs
	@echo "rebuilding database"

bin.%: SCRIPT=bin/pyf$(subst bin.,,$@)
bin.%:
	@echo "running $(SCRIPT)"
	@$(PYTHON) $(SCRIPT)