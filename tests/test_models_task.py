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

from uuid import UUID

from utcore import ModelTestCase
from pyfarm.flaskapp import db
from pyfarm.models.task import Task, TaskModel


class TestTaskModel(ModelTestCase):
    def test_basic_insert(self):
        job, frame, parent_task, priority, attempts, agent = range(6)
        task = Task(job, frame, parent_task=parent_task, priority=priority,
                    attempts=attempts, agent=agent)

        self.assertIsNone(task.id)
        db.session.add(task)
        db.session.commit()
        self.assertIsInstance(task.id, UUID)

        result = Task.query.filter_by(id=task.id).first()
        self.assertIsNotNone(result)
        self.assertEqual(result.id, task.id)
        self.assertEqual(result._jobid, job)
        self.assertEqual(result.frame, frame)
        self.assertEqual(result._parenttask, parent_task)
        self.assertEqual(result.priority, priority)
        self.assertEqual(result.attempts, attempts)
        self.assertEqual(result._agentid, agent)
        self.assertEqual(result.state, TaskModel.STATE_DEFAULT)

    def test_init_errors(self):
        job, frame, parent_task, priority, attempts, agent = range(6)

        with self.assertRaises(ValueError):
            raise NotImplementedError

