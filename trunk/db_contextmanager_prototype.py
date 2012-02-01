# No shebang line, this module is meant to be imported
#
# This file is part of PyFarm.
# Copyright (C) 2008-2012 Oliver Palmer
#
# PyFarm is free software: you can redistribute it and/or modify
# it under the terms of the GNU Lesser General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# PyFarm is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public License
# along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.

'''simple script to prototype and test new code'''

import types
import random
import sqlalchemy as sql
from sqlalchemy.orm import mapper, sessionmaker

engine = sql.create_engine('sqlite:///:memory:', echo=True)
metadata = sql.MetaData()

# setup test table
hosts = sql.Table('prototype_hosts', metadata,
    sql.Column('id', sql.Integer, autoincrement=True, primary_key=True),
    sql.Column('hostname', sql.String(36)),
    sql.Column('ram_usage', sql.Integer)
)

# setup metadata and bind engine
metadata.create_all(engine)
metadata.bind = engine
insert = hosts.insert()

# insert test data
for i in range(1, 5):
    data = {
        "hostname" : "hostname%02i" % i,
        "ram_usage" : 0
    }
    insert.execute(data)


class Query(object):
    '''
    Query context manager that only commits if data
    has changed

    :param sqlalchemy.Table table:
        sql table to operate on

    :param object base:
        base relation object to map to
    '''
    def __init__(self, table, base=None):
        self.__base = base

        # alternative (empty) base object
        class Base(object):
            pass

        if not isinstance(table, sql.Table):
            raise TypeError("table must be an instance of sqlalchemy.Table")

        # bind and configure engine
        mapper(self.__base or Base, table)
        Session = sessionmaker()
        Session.configure(bind=engine)

        # setup class variables
        self.session = Session()
        self.query = self.session.query(Base)

        # add a convenience method
        self.query.rollback = self.session.rollback
    # end __init__

    def __enter__(self):
        print "opening session"
        return self.query
    # end __enter__

    def __exit__(self, type, value, trackback):
        if self.session.dirty or self.session.new:
            print "committing data"
            self.session.commit()
    # end __exit__
# end Query

# set data
with Query(hosts) as query:
    host = query.filter_by(hostname="hostname01").first()
    host.ram_usage = random.randint(10, 1024)

# retrieve data
with Query(hosts) as query:
    host = query.filter_by(hostname="hostname01").first()
    print "%s is using %imb ram" % (host.hostname, host.ram_usage)
#with Query(hosts) as query:
#    host = query.filter_by(hostname="hostname01").first()
#    host.ram_usage = random.randint(10, 1024)
#    raise Query.AbortCommit('testing abort')


#    query = session.query(session.base)

#class Host(object):
#    pass
#
#
#
#mapper(Host, hosts)
#
#Session = sessionmaker()
##print a
##Session.configure(bind=engine)
#session = Session()
#query = session.query(Host)
#
#
#host = query.filter_by(hostname="hostname01").first()
#host.ram_usage = random.randint(10, 1024)
#print "session dirty: %s" % bool(session.dirty)
#session.commit()
#
#query = session.query(Host)
#host = query.filter_by(hostname="hostname01").first()
##if not :
#print "session dirty: %s" % bool(session.dirty)
#print "====",host.ram_usage
