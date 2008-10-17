#!/usr/bin/env python
# POP mailbox downloader with deletion - Chapter 11
# download-and-delete.py
############################
# WARNING: This program deletes mail from the specified mailbox.
#          DO NOT point it to any mailbox you care about!
############################

import getpass, poplib, sys, email

def log(text):
    """Simple function to write status information"""
    sys.stdout.write(text)
    sys.stdout.flush()

(host, user, dest) = sys.argv[1:]
passwd = getpass.getpass()

# Open a mailbox for appending
destfd = open(dest, "at")

log("Connecting to %s...\n" % host)
p = poplib.POP3(host)
try:
    log("Logging on...")
    p.user(user)
    p.pass_(passwd)
    log(" success.\n")
except poplib.error_proto, e:
    print "Login failed:", e
    sys.exit(1)

# Load list of messages present in mailbox
log("Scanning INBOX...")
mblist = p.list()[1]
log(" %d messages.\n" % len(mblist))

dellist = []

# Iterate over the list of messages in the mailbox
for item in mblist:
    number, octets = item.split(' ')
    log("Downloading message %s (%s bytes)..." % (number, octets))

    # Retrieve the message (storing it in a list of lines)
    lines = p.retr(number)[1]

    # Create an e-mail object representing the message
    msg = email.message_from_string("\n".join(lines))

    # Write it out to the mailbox
    destfd.write(msg.as_string(unixfrom = 1))

    # Make sure there's an extra newline separating messages
    destfd.write("\n")

    # Add it to the list of messages to delete later
    dellist.append(number)

    log(" done.\n")

# Close the mailbox
destfd.close()

counter = 0    # Just a convenience for the status messages

# Iterate over the list of messages to delete
for number in dellist:
    counter += 1
    log("Deleting message %d of %d\r" % (counter, len(dellist)))

    # Delete the message.
    p.dele(number)

# Display summary information
if counter > 0:
    log("Successfully deleted %d messages from server.\n" % counter)
else:
    log("No messages present to download.\n")

log("Closing connection... ")

# Log out
p.quit()
log(" done.\n")

