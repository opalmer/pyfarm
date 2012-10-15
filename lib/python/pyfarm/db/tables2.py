import datetime

try:
    from collections import OrderedDict
except ImportError:
    from ordereddict import OrderedDict

from pyfarm.datatypes.enums import State, ACTIVE_HOSTS_FRAME_STATES

from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy.orm import relationship, sessionmaker
from sqlalchemy import Column, Integer, String, DateTime, ForeignKey
from sqlalchemy import create_engine, orm, and_

engine = create_engine('sqlite:///:memory:', echo=True)

class PyFarmBase(object):
    '''
    base class which defines some base functions and attributes
    for all classes to inherit
    '''
    repr_attrs = ()

    # base column definitions which all other classes inherit
    id = Column(Integer, primary_key=True, autoincrement=True)

    def __repr__(self):
        values = []

        for attr in self.repr_attrs:
            original_value = getattr(self, attr)

            # for unicode use a slightly nicer representation
            if isinstance(original_value, unicode):
                value = "'%s'"  % original_value
            else:
                value = repr(original_value)

            values.append("%s=%s" % (attr, value))

        return "%s(%s)" % (self.__class__.__name__, ", ".join(values))
    # __repr__
# end PyFarmBase

Base = declarative_base(cls=PyFarmBase)
Base.metadata.bind = engine

class Host(Base):
    '''base host definition'''

    # table setup and string representation attributes
    __tablename__ = "pyfarm_hosts"
    repr_attrs = ("id", "hostname")

    # column definitions
    hostname = Column(String(255), nullable=False, unique=True)

    # relational definitions
    software = relationship('Software', uselist=True, backref=__tablename__)
    groups = relationship('Group', uselist=True, backref=__tablename__)
    running_frames = relationship(
        'Frame', uselist=True, backref=__tablename__,
        primaryjoin=lambda: and_(
            Frame.hostid == Host.id,
            Frame.state.in_(ACTIVE_HOSTS_FRAME_STATES)
        )
    )

    def __init__(self, hostname):
        self.hostname = hostname
    # end __init__
# end Host


class Software(Base):
    '''stores information about what software a host can run'''
    # table setup and string representation attributes
    __tablename__ = "pyfarm_host_software"
    repr_attrs = ("host", "name")

    # column definitions
    host = Column(Integer, ForeignKey(Host.id))
    name = Column(String(128), nullable=False)
    hosts = relationship('Host', uselist=True, backref=__tablename__)

    def __init__(self, host, name):
        self.host = host
        self.name = name
    # end __init__
# end Software


class Group(Base):
    '''stores information about which group or groups a host belongs to'''
    # table setup and string representation attributes
    __tablename__ = "pyfarm_host_group"
    repr_attrs = ("host", "name")

    # column definitions
    host = Column(Integer, ForeignKey(Host.id), nullable=False)
    name = Column(String(128), nullable=False)

    # relationship setup
    hosts = relationship('Host', uselist=True, backref=__tablename__)

    def __init__(self, host, name):
        self.host = host
        self.name = name
    # end __init__
# end Group


class Job(Base):
    '''base job definition'''
    # table setup and string representation attributes
    __tablename__ = "pyfarm_jobs"
    repr_attrs = ("id",)

    # relationship definitions
    frames = relationship('Frame', uselist=True, backref=__tablename__)
# end Job


class Frame(Base):
    __tablename__ = "pyfarm_frames"
    repr_attrs = ("frame", "host")

    # column setup
    jobid = Column(Integer, ForeignKey(Job.id))
    hostid = Column(Integer, ForeignKey(Host.id))
    frame = Column(Integer, nullable=False)
    state = Column(Integer, default=State.QUEUED)

    # relationship definitions
    job = relationship('Job', uselist=False, backref=__tablename__)
    host = relationship(
        'Host', uselist=False, backref=__tablename__,
        primaryjoin="Frame.hostid == Host.id"
    )

    def __init__(self, jobid, frame, state=None, hostid=None):
        self.jobid = jobid
        self.frame = frame

        if state is None:
            state = State.QUEUED

        self.state = state

        if hostid is not None:
            self.hostid = hostid
    # end __init__
# end Frame

Base.metadata.create_all()
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
j = Job()
session.add(j)
jobrow = session.query(Job).first()

## add frames
f = Frame(jobrow.id, 21, state=State.FAILED, hostid=hostrow.id)
session.add(f)
f = Frame(jobrow.id, 21, state=State.ASSIGN, hostid=hostrow.id)
session.add(f)
f = Frame(jobrow.id, 22, state=State.RUNNING, hostid=hostrow.id)
session.add(f)
#f = Frame(jobrow.id, 21, state=State.ASSIGN, hostid=2)
#session.add(f)

session.commit()

host = session.query(Host).filter(Host.hostname == hostname).first()
print host.running_frames[0].job

group = session.query(Group).filter(Group.name == 'group1').first()
print group.hosts[0].running_frames
