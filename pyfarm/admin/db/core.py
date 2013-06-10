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


class CoreTableView(ModelView):
    """
    Subclass of the standard :class:`ModelView` which takes into
    account contextual information such as a task or agent's state.
    """
    form_args = {}
    form_overrides = {}
    column_formatters = {}

    def __init__(self, model, session=db.session, **kwargs):
        kwargs.setdefault("category", "Tables")

        # if the model has a state attribute then we need to create
        # a menu to display the state
        if hasattr(model, "state") and model.STATE_ENUM is not None:
            self.form_overrides.update(state=SelectField)

            form_args = []
            for value in sorted(model.STATE_ENUM.values()):
                key = model.STATE_ENUM.get(value)
                form_args.append((value, key.capitalize()))

            self.form_args.update(state={"choices": form_args, "coerce": int})
            self.column_formatters.update(state=
                lambda view, context, model, name:
                    model.STATE_ENUM.get(model.state).capitalize())

        # if the model has an os (operating system) attribute then
        # well need the proper column formatter and input handler
        if hasattr(model, "os") and model.OS_ENUM is not None:
            self.form_overrides.update(os=SelectField)

            form_args = []
            for value in sorted(model.OS_ENUM.values()):
                key = model.OS_ENUM.get(value)
                form_args.append((value, key.capitalize()))

            self.form_args.update(os={"choices": form_args, "coerce": int})
            self.column_formatters.update(os=
                lambda view, context, model, name:
                    model.OS_ENUM.get(model.os).capitalize())

        if "endpoint" in kwargs:
            kwargs["endpoint"] = "table/%s" % kwargs["endpoint"]

        super(CoreTableView, self).__init__(model, session, **kwargs)