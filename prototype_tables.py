from sqlalchemy.orm import sessionmaker
from pyfarm.db.tables import init, engine, Host, HostSoftware, HostGroup, \
    Master, Job, Frame

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

main_job = Job('ping', ['-c', '1', 'localhost'], 1, 5)
session.add(main_job)
session.commit()

main_job.createFrames()

print main_job
for f in main_job.frames:
    print f, f.job
