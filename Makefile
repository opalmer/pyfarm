# Copyright 2013 Oliver Palmer
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#    http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.

DB_ECHO ?= True
DB_REBUILD ?= True
PYTHON ?= python
PYTHONPATH ?= lib/python
CONFIG_DIR ?= etc
CONFIG_FILES ?= $(wildcard $(CONFIG_DIR)/*.yml.template)

shell:
	@env PYTHONPATH=$(PYTHONPATH) ipython

clean:
	@python setup.py clean

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

test.%: SCRIPT=tests/test_$(subst test.,,$@).py
test.%:
	@echo "running $(SCRIPT)"
	@env PYTHONPATH=$(PYTHONPATH) $(PYTHON) $(SCRIPT)

docs.%: TARGET=$(subst docs.,,$@)
docs.%:
	@python setup.py develop
	$(MAKE) -s -C docs $(TARGET)

tests: test.preferences
