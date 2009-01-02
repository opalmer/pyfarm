
class Broadcast(object):
    '''
    Class used to send a broadcast signal to all computers on a network

    OUTPUT:
        Each function outputs an array3:
            [ip,port,hostname]
    '''
    def __init__(self, port=51423, host=''):
        #super(Broadcast, self).__init__()
        # network setup
        self.log = FarmLog("Network.Broadcast()")
        self.log.setLevel('debug')
        self.port = port
        self.host = host
        self.dest = ('<broadcast>', self.port)
        self.sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)

        # nodes/hostname setup
        self.name = System().name()
        self.nodes = []

    def getNodes(self):
        '''
        Send the broadcast packet accross the network

        NOTICE: You MUST start the clients first!
        '''
        try:
            self.sock.setsockopt(socket.SOL_SOCKET, socket.SO_BROADCAST, 1)
            self.sock.sendto(self.name, self.dest)

            self.log.info("Looking for nodes; press Ctrl-C to stop.")
            while True:
                (hostname, address) = self.sock.recvfrom(2048)
                if not len(hostname):
                   break

                self.log.debug("Found Node: %s @ %s:%s" % (hostname, address[0], address[1]))

        except (KeyboardInterrupt, SystemExit):
            pass

    def node(self):
        '''Receieve the broadcast packet and reply to the host'''
        self.sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
        self.sock.setsockopt(socket.SOL_SOCKET, socket.SO_BROADCAST, 1)
        self.sock.bind((self.host, self.port))
        self.log.info("Looking for nodes; press Ctrl-C to stop.")

        while True:
            try:
                hostname, address = self.sock.recvfrom(8192)
                self.log.debug("Found Server: %s @ %s:%s" % (hostname, address[0], address[1]))

                # Acknowledge the multicast packet
                self.sock.sendto(self.name, address)
            except (KeyboardInterrupt, SystemExit):
                sys.exit(self.log.critical('PROGRAM TERMINATED'))
