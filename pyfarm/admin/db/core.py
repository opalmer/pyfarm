# No shebang line, this module is meant to be imported
#
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

from wtforms.fields import SelectField
from flask.ext.admin.contrib.sqlamodel import ModelView
from pyfarm.flaskapp import db


class CoreDatabaseModelView(ModelView):
    """
    Subclass of the standard :class:`ModelView` which takes into
    account contextual information such as a task or agent's state.
    """

    # TODO: make part of the instance so subclasses can add to
    # TODO: STATE needs a formatter
    # column_formatters

    def __init__(self, model, session=db.session, **kwargs):
        kwargs.setdefault("category", "Database")

        # if the model has a state attribute then we need to create
        # a menu to display the state
        if hasattr(model, "state") and model.STATE_ENUM is not None:
            self.form_overrides = {"state": SelectField}

            form_args = []
            for value in sorted(model.STATE_ENUM.values()):
                key = model.STATE_ENUM.get(value)
                form_args.append((value, key.capitalize()))

            self.form_args = {"state": {"choices": form_args, "coerce": int}}

        super(CoreDatabaseModelView, self).__init__(model, session, **kwargs)
