DB_ECHO = True
DB_REBUILD = True
PYTHON = python

shell:
	@env PYTHONPATH=. ipython

clean:
	@find . -name "*.pyc" -exec rm -v {} \;
	@find . -name "*.pyo" -exec rm -v {} \;

jobs:
	@$(PYTHON) ../dev/bin/make_jobs.py

db:
	@echo "rebuilding database"
	@$(PYTHON) -c 'from common.db import tables; tables.init(rebuild=True)'
	$(MAKE) jobs

pyfarm.%: SCRIPT=pyfarm_$(subst pyfarm.,,$@.py)
pyfarm.%:
	@echo "Running $(SCRIPT)"
	@$(PYTHON) $(SCRIPT)

run.%: SCRIPT=run_$(subst run.,,$@.py)
run.%:
	@echo "Running $(SCRIPT)"
	@$(PYTHON) $(SCRIPT)