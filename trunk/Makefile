DB_ECHO = True
DB_REBUILD = True
PYTHON = $(shell which python)

shell:
	@env PYTHONPATH=. PYFARM_STDOUT_LOGGING=false ipython

clean:
	@find . -name "*.pyc" -exec rm -v {} \;
	@find . -name "*.db" -exec rm -v {} \;

db:
	@$(PYTHON) -c 'from common.db import tables; from common import preferences;\
	preferences.DB_ECHO = $(DB_ECHO); preferences.DB_REBUILD = $(DB_REBUILD); tables.init()'

pyfarm.%: SCRIPT=pyfarm_$(subst pyfarm.,,$@.py)
pyfarm.%:
	@echo "Running $(SCRIPT)"
	@$(PYTHON) $(SCRIPT)

run.%: SCRIPT=run_$(subst run.,,$@.py)
run.%:
	@echo "Running $(SCRIPT)"
	@$(PYTHON) $(SCRIPT)