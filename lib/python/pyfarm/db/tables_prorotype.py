from sqlalchemy.orm import sessionmaker
from pyfarm.db.tables import init, engine, Host, HostSoftware, Master

init(True) # setup tables
Session = sessionmaker(bind=engine)
session = Session()

master = Master('master', '192.168.1.1', '255.255.255.0')
session.add(master)
session.commit()

host1 = Host('host1', '192.168.1.2', '255.255.255.0', 9030, enabled=True, master=master)
host2 = Host('host2', '192.168.1.3', '255.255.255.0', 9030, enabled=False, master=master)
session.add(host1)
session.add(host2)
session.commit()

print host1.master
