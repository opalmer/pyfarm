DB_ECHO = True
DB_REBUILD = True

shell:
	@env PYTHONPATH=. PYFARM_STDOUT_LOGGING=false ipython

clean:
	@find . -name "*.pyc" -exec rm -v {} \;
	@find . -name "*.db" -exec rm -v {} \;

db:
	@python -c 'from common.db import tables; from common import preferences;\
	preferences.DB_ECHO = $(DB_ECHO); preferences.DB_REBUILD = $(DB_REBUILD); tables.init()'