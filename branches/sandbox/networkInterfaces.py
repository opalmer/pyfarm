import pprint
from PyQt4 import QtNetwork

class AddressEntry(object):
    '''
    Holds properties related to a network interface such as ip, broadcast,
    and netmask.
    
    @param addressEntry: Source object containing the address entry
    @type  addressEntry: QtNetwork.QNetworkAddressEntry
    '''
    def __init__(self, ):
    

class NetworkInterface(object):
    '''
    Receives information from and creates properties around a 
    network interface
    
    @param interface: The interface to create the properties from
    @type  interface: QtNetwork.QNetworkInterface
    '''
    def __init__(self, interface):
        self.interface = interface
        self.name      = str(interface.name())
        self.humanName = str(interface.humanReadableName())
        self.mac       = str(interface.hardwareAddress())
        self.addresses = self._addresses()
        
        # setup network addresses
        addresses = []
        for addr in interface.addressEntries():
            addresses.append(NetworkAddress(addr))
        self.addresses = addresses or None
        
        self.isLocal   = self._isLocal()
        self.isValid   = self._isValid()
        
    def _addresses(self):
        '''Find and return all addresses (converted to strings'''
        pass

    def _isLocal(self):
        '''Return true of the interface is for the localhost'''
        return True

    def _isValid(self):
        '''
        Evalulate the interface object and return true if the interface contains
        valid information and a non-local address.
        '''
        if not self.interface.isValid() or not self.addresses:
            return False
        
        return True

def interfaces():
    '''Returns a list of interface objects'''
    for interface in QtNetwork.QNetworkInterface.allInterfaces():
        iFace = NetworkInterface(interface)
        if iFace.isValid:
            yield iFace
            

if __name__ == '__main__':
    for interface in interfaces():
        print interface.addresses[0].ip

#    data = {}
#    for interface in QtNetwork.QNetworkInterface.allInterfaces():
#        entry = data[str(interface.humanReadableName())] = {}
#        entry['name']      = str(interface.name())
#        entry['humanName'] = str(interface.humanReadableName())
#        entry['mac']       = str(interface.hardwareAddress())
#        entry['valid']     = str(interface.isValid())
#    
#        addresses = entry['addresses'] = {}
#        for address in interface.addressEntries():
#            if address.ip().protocol():  # IPv6
#                entry = addresses['IPv6'] = {}
#            else:                        # IPv4
#                entry = addresses['IPv4'] = {}
#            entry['broadcast'] = str(address.broadcast().toString())
#            entry['ip']        = str(address.ip().toString())
#            entry['netmask']   = str(address.netmask().toString())
#            entry['prefix']    = str(address.prefixLength())