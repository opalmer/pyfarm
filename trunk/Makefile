DB_ECHO = True
DB_REBUILD = True
PYTHON = python

shell:
	@env PYTHONPATH=. ipython

clean:
	@echo "running clean"
	@find . -name "*.pyc" -exec rm {} \;
	@find . -name "*.pyo" -exec rm {} \;
	@find . -name "*~" -exec rm {} \;

jobs:
	@$(PYTHON) ../dev/bin/make_jobs.py

db:
	@echo "rebuilding database"
	@$(PYTHON) -c 'from pyfarm.db import tables; tables.init(rebuild=True)'
	$(MAKE) jobs

pyfarm.%: SCRIPT=pyfarm_$(subst pyfarm.,,$@.py)
pyfarm.%:
	@echo "running $(SCRIPT)"
	@$(PYTHON) $(SCRIPT)

run.%: SCRIPT=run_$(subst run.,,$@.py)
run.%:
	@echo "running $(SCRIPT)"
	@$(PYTHON) $(SCRIPT)
