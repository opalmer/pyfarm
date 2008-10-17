#!/usr/bin/env python
# Echo Server with Forking and Forking Error Detection - Chapter 20
# errorserver.py

import socket, traceback, os, sys

def reap():
    while 1:
        try:
            result = os.waitpid(-1, os.WNOHANG)
            if not result[0]: break
        except:
            break
        print "Reaped child process %d" % result[0]

host = ''                               # Bind to all interfaces
port = 51423

s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
s.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
s.bind((host, port))
s.listen(1)

while 1:
    try:
        clientsock, clientaddr = s.accept()
    except KeyboardInterrupt:
        raise
    except:
        traceback.print_exc()
        continue

    # Clean up old children.
    reap()

    # Fork a process for this connection.
    try:
        pid = os.fork()
    except:
        print "BAD THING HAPPENED: fork failed"
        clientsock.close()
        continue

    if pid:
        # This is the parent process.  Close the child's socket
        # and return to the top of the loop.
        clientsock.close()
        continue
    else:
        print "New child", os.getpid()
        # From here on, this is the child.
        s.close()                           # Close the parent's socket
            
        # Process the connection

        try:
            print "Got connection from", clientsock.getpeername()
            while 1:
                data = clientsock.recv(4096)
                if not len(data):
                    break
                clientsock.sendall(data)
        except (KeyboardInterrupt, SystemExit):
            raise
        except:
            traceback.print_exc()

        # Close the connection

        try:
            clientsock.close()
        except KeyboardInterrupt:
            raise
        except:
            traceback.print_exc()

        # Done handling the connection.  Child process *must* terminate
        # and not go back to the top of the loop.
        sys.exit(0)

