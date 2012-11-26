from sqlalchemy.orm import sessionmaker
from pyfarm.db.tables import init, engine, Host, HostSoftware, HostGroup, Master

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

maya1 = HostSoftware('maya', host1)
maya2 = HostSoftware('maya', host2)
hou1 = HostSoftware('houdini', host1, '12+')
hou2 = HostSoftware('houdini', host2, version='12+')

# add and commit
objs = [maya1, maya2, hou1, hou2]
map(session.add, objs)
session.commit()

host1group = HostGroup('host1group', host1)
host2group = HostGroup('host2group', host2)
host1group1 = HostGroup('group1', host1)
host1group2 = HostGroup('group2', host1)
host2group1 = HostGroup('group1', host2)
host2group2 = HostGroup('group2', host2)

# add and commit
objs = (host1group, host2group, host1group1, host1group2, host2group1, host2group2)
map(session.add, objs)
session.commit()

#print host1group1.hosts
