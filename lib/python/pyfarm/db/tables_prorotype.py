try:
    from collections import OrderedDict
except ImportError:
    from ordereddict import OrderedDict

from pyfarm.datatypes.enums import State

from sqlalchemy.orm import sessionmaker

from pyfarm.db.tables import init, engine, Frame, Host, Group, Software, \
    Job, Dependency

init(True) # setup tables
Session = sessionmaker(bind=engine)
session = Session()

# add a host
hostname = 'vmworkstation'
host = Host(hostname)
session.add(host)
hostrow = session.query(Host).filter(Host.hostname == hostname).first()

# add software
s = Software(hostrow.id, 'test')
session.add(s)
s = Software(hostrow.id, 'test2')
session.add(s)

# add groups
g = Group(hostrow.id, 'group1')
session.add(g)
g = Group(hostrow.id, 'group2')
session.add(g)

# add a job
j = Job(1,2)
session.add(j)
j = Job(1,2,state=State.RUNNING)
session.add(j)
j = Job(1,2,state=State.DONE)
session.add(j)

session.commit()

jobs = session.query(Job).all()
dependency = Dependency(jobs[0], jobs[1])
session.add(dependency)
dependency = Dependency(jobs[0], jobs[2])
session.add(dependency)
session.commit()

jobs = session.query(Job).all()

for job in jobs:
    print job, job.dependencies


#
#
## add frames
#f = Frame(jobrow.id, 20, state=State.QUEUED, hostid=hostrow.id)
#session.add(f)
#f = Frame(jobrow.id, 21, state=State.ASSIGN, hostid=hostrow.id)
#session.add(f)
#f = Frame(jobrow.id, 22, state=State.RUNNING, hostid=hostrow.id)
#session.add(f)
#f = Frame(jobrow.id, 23, state=State.FAILED, hostid=hostrow.id)
#session.add(f)



#host = session.query(Host).filter(Host.hostname == hostname).first()
##print host.running_frames[0].job
#
#group = session.query(Group).filter(Group.name == 'group1').first()
##print group.hosts[0].running_frames
#
#print '============', j
