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


class CoreTableModelView(ModelView):
    """
    Subclass of the standard :class:`ModelView` which takes into
    account contextual information such as a task or agent's state.
    """
    STATE_DEFAULT = None

    def __init__(self, model, session=db.session, **kwargs):
        kwargs.setdefault("category", "Database")
        kwargs.setdefault("endpoint", "tables/%s" % model.__tablename__)

        if self.form_overrides is None:
            self.form_overrides = {}

        if self.form_args is None:
            self.form_args = {}

        # if the model has a state attribute then we need to create
        # a menu to display the state
        if hasattr(model, "state") and model.STATE_ENUM is not None:
            self.form_overrides.update(state=SelectField)

            form_args = []
            default_index = None
            for index, value in enumerate(sorted(model.STATE_ENUM.values())):
                key = model.STATE_ENUM.get(value)
                form_args.append((value, key.capitalize()))

                # set the default index if it matches STATE_DEFAULT
                if model.STATE_DEFAULT == value:
                    default_index = index

            # if a default index was provided then use it to
            # reorder the state menu
            if default_index is not None:
                default_value = model.STATE_ENUM.get(
                    model.STATE_DEFAULT).capitalize()
                value = (model.STATE_DEFAULT, default_value)
                print value
                index = form_args.index(value)
                del form_args[index]
                form_args.insert(0, value)

            self.form_args.update(state={"choices": form_args, "coerce": int})

        super(CoreTableModelView, self).__init__(model, session, **kwargs)
