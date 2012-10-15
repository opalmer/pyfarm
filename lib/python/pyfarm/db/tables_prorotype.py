from pyfarm.db.tables.base import PyFarmBase

try:
    from collections import OrderedDict
except ImportError:
    from ordereddict import OrderedDict

from pyfarm.datatypes.enums import State, \
    ACTIVE_HOSTS_FRAME_STATES, ACTIVE_FRAME_STATES, ACTIVE_JOB_STATES

from sqlalchemy.orm import relationship, sessionmaker
from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy import Column, Integer, String, ForeignKey
from sqlalchemy import create_engine, and_

# recreate the base for testing
from pyfarm.db.tables import PyFarmBase
engine = create_engine("sqlite:///:memory:", echo=False)
Base = declarative_base(cls=PyFarmBase, bind=engine)

class Host(Base):
    '''base host definition'''

    # table setup and string representation attributes
    __tablename__ = "pyfarm_hosts"
    repr_attrs = ("id", "hostname")

    # column definitions
    hostname = Column(String(255), nullable=False, unique=True)

    # relational definitions
    software = relationship('Software', uselist=True, backref="ref_software")
    groups = relationship('Group', uselist=True, backref="ref_groups")
    running_frames = relationship(
        'Frame', uselist=True, backref="ref_running_frames",
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
    hosts = relationship('Host', uselist=True, backref="ref_hosts")

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
    host = Column(Integer, ForeignKey("pyfarm_hosts.id"), nullable=False)
    name = Column(String(128), nullable=False)

    # relationship setup
    hosts = relationship('Host', uselist=True, backref=__tablename__)

    def __init__(self, host, name):
        self.host = host
        self.name = name
    # end __init__
# end Group




class Frame(Base):
    __tablename__ = "pyfarm_frames"
    repr_attrs = ("frame", "host")

    # column setup
    jobid = Column(Integer, ForeignKey("pyfarm_jobs.id"))
    hostid = Column(Integer, ForeignKey("pyfarm_hosts.id"))
    frame = Column(Integer, nullable=False)
    state = Column(Integer, default=State.QUEUED)

    # relationship definitions
    job = relationship('Job', uselist=False, backref="ref_job")
    host = relationship(
        'Host', uselist=False, backref="ref_host",
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

class Dependency(Base):
    __tablename__ = "pyfarm_job_dependency"

    jobid = Column(Integer, ForeignKey("pyfarm_jobs.id"))
    dependency = Column(Integer, ForeignKey("pyfarm_jobs.id"))
# end Dependency



class Job(Base):
    '''base job definition'''
    # table setup and string representation attributes
    __tablename__ = "pyfarm_jobs"
    repr_attrs = ("id",)

    state = Column(Integer, default=State.QUEUED)

    # relationship definitions
    frames = relationship(
        'Frame', uselist=True, backref="ref_frames",
        doc="list all frames which are assigned to the job"
    )
    running_frames = relationship(
        'Frame', uselist=True, backref="ref_running_frames",
        primaryjoin=lambda: and_(
            Job.id == Frame.jobid,
            Frame.state.in_(ACTIVE_FRAME_STATES)
        )
    )

    # TODO: this does not work
    # TODO: column properties: http://docs.sqlalchemy.org/en/rel_0_7/orm/mapper_config.html#using-column-property
#    dependson = relationship(
#        'Dependency', secondary="pyfarm_job_dependency", uselist=True, backref="ref_dependson",
#        primaryjoin=lambda: Dependency.jobid == Job.id,
#        secondaryjoin=lambda: Job.state.in_(ACTIVE_JOB_STATES),
#    )
# end Job

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

# add frames
f = Frame(jobrow.id, 21, state=State.FAILED, hostid=hostrow.id)
session.add(f)
f = Frame(jobrow.id, 21, state=State.ASSIGN, hostid=hostrow.id)
session.add(f)
f = Frame(jobrow.id, 22, state=State.RUNNING, hostid=hostrow.id)
session.add(f)

session.commit()

host = session.query(Host).filter(Host.hostname == hostname).first()
print host.running_frames[0].job

group = session.query(Group).filter(Group.name == 'group1').first()
print group.hosts[0].running_frames

print "==",j.running_frames[0]
