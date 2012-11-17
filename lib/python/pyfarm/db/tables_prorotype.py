try:
    from collections import OrderedDict

except ImportError:
    from ordereddict import OrderedDict

from pyfarm.datatypes.enums import State

from sqlalchemy.orm import sessionmaker

from pyfarm.db.tables import init, engine, Frame, Host, Group, Software, \
    Job, Dependency, Master

init(True) # setup tables
Session = sessionmaker(bind=engine)
session = Session()

# add a host
hostname = 'vmworkstation'

# create and insert a master
master = Master(hostname, '255.255.255.0', '255.255.255.0', 9030)
session.add(master)
masterrow = session.query(Master).filter(Master.hostname == hostname).first()

# create and insert a host
host = Host(
    hostname, '255.255.255.0', '255.255.255.0', 9030,
    masterid=masterrow.id # testing foreign key
)
session.add(host)

# add a job
j = Job('ping', ['-c', '1', 'localhost'], 1, 2)
session.add(j)
j = Job('ping', ['-c', '1', 'localhost'], 1, 2, state=State.RUNNING)
session.add(j)
j = Job('ping', ['-c', '1', 'localhost'], 1, 2, state=State.DONE)
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
    print job
    for i in job.dependencies:
        print "\tframes: %s" % i.frames

job = jobs[0]


# add frames
f = Frame(job.id, 20, state=State.QUEUED, hostid=host.id)
session.add(f)
f = Frame(job.id, 21, state=State.ASSIGN, hostid=host.id)
session.add(f)
f = Frame(job.id, 22, state=State.RUNNING, hostid=host.id)
session.add(f)
f = Frame(job.id, 23, state=State.FAILED, hostid=host.id)
session.add(f)

session.commit()

print "="*20
jobs = session.query(Job).all()
for job in jobs:
    print job
    print "\tframes: %s" % job.frames
