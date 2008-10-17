#!/usr/bin/env python
# Locking server with Forking - Chapter 20 - lockingserver.py
# NOTE: lastaccess.txt will be overwritten!

import socket, traceback, os, sys, fcntl, time

def getlastaccess(fd, ip):
    """Given a file descriptor and an IP, finds the date of last access
    from that IP in the file and returns it.  Returns None if there was
    never an access from that IP."""

    # Acquire a shared lock.  We don't care of others are reading the file
    # right now, but they shouldn't be writing it.
    fcntl.flock(fd, fcntl.LOCK_SH)
    
    try:
        # Start at the beginning of the file
        fd.seek(0)
        
        for line in fd.readlines():
            fileip, accesstime = line.strip().split("|")
            if fileip == ip:
                # Got a match -- return it
                return accesstime
        return None
    finally:
        # Make sure the lock is released no matter what
        fcntl.flock(fd, fcntl.LOCK_UN)

def writelastaccess(fd, ip):
    """Update file noting new last access time for the given IP."""

    # Acquire an exclusive lock.  Nobody else can modify the file
    # while it's being used here.
    fcntl.flock(fd, fcntl.LOCK_EX)
    records = []
    
    try:
        # Read the existing records, *except* the one for this IP.
        fd.seek(0)
        for line in fd.readlines():
            fileip, accesstime = line.strip().split("|")
            if fileip != ip:
                records.append((fileip, accesstime))

        fd.seek(0)
        
        # Write them back out, *plus* the one for this IP.
        for fileip, accesstime in records + [(ip, time.asctime())]:
            fd.write("%s|%s\n" % (fileip, accesstime))
        fd.truncate()
    finally:
        # Release the lock no matter what
        fcntl.flock(fd, fcntl.LOCK_UN)

def reap():
    """Collect any waiting child processes."""
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
fd = open("lastaccess.txt", "w+")

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
    pid = os.fork()

    if pid:
        # This is the parent process.  Close the child's socket
        # and return to the top of the loop.
        clientsock.close()
        continue
    else:
        # From here on, this is the child.
        s.close()                           # Close the parent's socket
            
        # Process the connection

        try:
            print "Got connection from %s, servicing with PID %d" % \
                  (clientsock.getpeername(), os.getpid())
            ip = clientsock.getpeername()[0]
            clientsock.sendall("Welcome, %s.\n" % ip)
            last = getlastaccess(fd, ip)
            if last:
                clientsock.sendall("I last saw you at %s.\n" % last)
            else:
                clientsock.sendall("I've never seen you before.\n")

            writelastaccess(fd, ip)
            clientsock.sendall("I have noted your connection at %s.\n" % \
                    getlastaccess(fd, ip))

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

