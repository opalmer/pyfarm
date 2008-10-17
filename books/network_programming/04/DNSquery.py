#!/usr/bin/env python
# DNS query program - Example 4 - DNSquery.py

import sys, DNS, DNSany, re

def getreverse(query):
    """Given the query, returns an appropriate reverse lookup string
    under IN-ADDR.ARPA if query is an IP address; otherwise, returns None.
    This function is not IPv6-compatible."""
    if re.search('^\d+\.\d+\.\d+\.\d+$', query):
        octets = query.split('.')
        octets.reverse()
        return '.'.join(octets) + '.IN-ADDR.ARPA'
    return None

def formatline(index, typename, descr, data):
    retval = "%-2s %-5s" % (index, typename)
    data = data.replace("\n", "\n         ")
    if descr != None and len(descr):
        retval += " %-12s" % (descr + ":")
    return retval + " " + data

DNS.DiscoverNameServers()
queries = [(sys.argv[1], DNS.Type.ANY)]
donequeries = []
descriptions = {'A': 'IP address',
                'TXT': 'Data',
                'PTR': 'Host name',
                'CNAME': 'Alias for',
                'NS': 'Name server'}
               
while len(queries):
    (query, qtype) = queries.pop(0)
    if query in donequeries:
        # Don't look up the same thing twice
        continue
    donequeries.append(query)
    print "-" * 77
    print "Results for %s (lookup type %s)" % \
          (query, DNS.Type.typestr(qtype))
    print
    rev = getreverse(query)
    if rev:
        print "IP address given; doing reverse lookup using", rev
        query = rev
        
    answers = DNSany.nslookup(query, qtype, verbose = 0)
    if not len(answers):
        print "Not found."

    count = 0
    for answer in answers:
        count += 1
        if answer['typename'] == 'MX':
            print formatline(count, answer['typename'],
                             'Mail server',
                             "%s, priority %d" % (answer['data'][1],
                                                  answer['data'][0]))
            queries.append((answer['data'][1], DNS.Type.A))
        elif answer['typename'] == 'SOA':
            data = "\n" + "\n".join([str(x) for x in answer['data']])
            print formatline(count, 'SOA', 'Start of authority', data)
        elif answer['typename'] in descriptions:
            print formatline(count, answer['typename'],
                             descriptions[answer['typename']], answer['data'])
        else:
            print formatline(count, answer['typename'], None,
                             str(answer['data']))
        if answer['typename'] in ['CNAME', 'PTR']:
            queries.append((answer['data'], DNS.Type.ANY))
        if answer['typename'] == 'NS':
            queries.append((answer['data'], DNS.Type.A))
            
