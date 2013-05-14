#!/usr/bin/env python
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

from sqlalchemy.orm import sessionmaker

from pyfarm.db.engines import engine
from pyfarm.db.tables import init, Host, HostSoftware, HostGroup, \
    Master, Job, Frame

from pyfarm.datatypes.enums import State

init(True) # setup tables
Session = sessionmaker(bind=engine)
session = Session()

master = Master('master', '192.168.1.1', '255.255.255.0')
session.add(master)
session.commit()

host1 = Host('host1', '192.168.1.2', '255.255.255.0', enabled=True, master=master)
host2 = Host('host2', '192.168.1.3', '255.255.255.0', enabled=False, master=master)

# add and commit
objs = (host1, host2)
map(session.add, objs)
session.commit()

main_job = Job('ping', ['-c', '1', 'localhost'], 1, 15)
session.add(main_job)
session.commit()

main_job.createFrames()


job_query = session.query(Job)
filtered = job_query.filter(
    Job.state.in_((
        State.QUEUED, State.RUNNING, State.FAILED
    ))
)

for job in filtered.all():
    print job.queued_frames()
