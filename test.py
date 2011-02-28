import pprint
from PyQt4 import QtNetwork

class NetworkInterface(object):
    def __init__(self, interface):
        self.interface = interface
        self.addresses = interface.addressEntries()
        self.name      = str(interface.name())
        self.humanName = str(interface.humanReadableName())
        self.mac       = str(interface.hardwareAddress())
        self.isLocal   = self._isLocal()
        self.isValid   = self._isValid()

    def _isLocal(self):
        '''Return true of the interface is for the localhost'''
        return True

    def _isValid(self):
        '''
        Evalulate the interface object and return true if the interface contains
        valid information and a non-local address.
        '''
        if not self.interface.isValid():
            return False

        #TODO: Add check for non-local address
        #TODO: Add check for AT LEAST ipv4 addresses

        return True

def interfaces():
    '''Returns a list of interface objects'''
    for interface in QtNetwork.QNetworkInterface.allInterfaces():
        if interface.isValid():
            yield NetworkInterface(interface)

for interface in interfaces():
    print interface.humanName

data = {}
for interface in QtNetwork.QNetworkInterface.allInterfaces():
    entry = data[str(interface.humanReadableName())] = {}
    entry['name']      = str(interface.name())
    entry['humanName'] = str(interface.humanReadableName())
    entry['mac']       = str(interface.hardwareAddress())
    entry['valid']     = str(interface.isValid())

    addresses = entry['addresses'] = {}
    for address in interface.addressEntries():
        if address.ip().protocol():  # IPv6
            entry = addresses['IPv6'] = {}
        else:                        # IPv4
            entry = addresses['IPv4'] = {}
        entry['broadcast'] = str(address.broadcast().toString())
        entry['ip']        = str(address.ip().toString())
        entry['netmask']   = str(address.netmask().toString())
        entry['prefix']    = str(address.prefixLength())

#pprint.pprint(data)