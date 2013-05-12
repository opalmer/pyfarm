from twisted.names import dns, server, client, cache
from twisted.application import service, internet

class Resolver(client.Resolver):
    def __init__(self, mappings, servers=None):
        self.mappings = {}

        if servers is None:
            # lazy import so we don't load the library
            # if we don't have to
            # TODO: optionally get default nameserver(s) from a config
            from dns.resolver import get_default_resolver
            pydns_resolver = get_default_resolver()
            servers = pydns_resolver.nameservers

        # preprocess mappings so every result is a tuple
        for key, value in mappings.iteritems():
            if isinstance(value, basestring):
                value = (value, )
            elif isinstance(value, list):
                value = tuple(value)

            self.mappings[key] = value

        client.Resolver.__init__(self, servers=servers)
        self.ttl = 10
    # end __init__

    def lookupAddress(self, name, timeout=None):
        mapped = self.mappings.get(name)
        if mapped is not None:
            # create dns record from strings
            record = lambda address: \
                dns.RRHeader(
                    name, dns.A, dns.IN, self.ttl,
                    dns.Record_A(address, self.ttl)
                )

            records = tuple(map(record, self.mappings[name]))
            return [records, (), ()]
        else:
            # TODO: fix me....fails currently due to data we're
            # passing off to the socket module
            def result(callback):
                print "======",callback
            lookup = self._lookup((name, ), dns.IN, dns.A, timeout)
            lookup.addCallback(lookup)

    # end lookupAddress
# end Resolver

application = service.Application("pyfarm.service.dns")

mappings = {
    "foo" : ["99.99.99.99", "1.2.3.4"]
}
resolver = Resolver(mappings)
factory = server.DNSServerFactory(
    caches=[cache.CacheResolver()],
    clients=[resolver]
)
protocol = dns.DNSDatagramProtocol(factory)

# register as tcp and udp
multi_service = service.MultiService()
PORT = 5300

# tcp/udp server setup
server_tcp = internet.TCPServer(PORT, factory)
server_udp = internet.UDPServer(PORT, protocol)
server_tcp.setServiceParent(multi_service)
server_udp.setServiceParent(multi_service)

# run all of the above as a twistd application
multi_service.setServiceParent(service.IServiceCollection(application))

if __name__ == '__main__':
    import sys
    print "usage: twistd -[n]y %s" % sys.argv[0]
