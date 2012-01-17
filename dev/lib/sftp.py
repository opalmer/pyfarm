"""
Uses paramiko to implement Server:
    simplified upload/download via SFTP

Usage:
    server = sftp.Server("user", "pass", "example.com")
    # upload a file
    server.upload("/local/path", "/remote/path")
    # download a file
    server.download("remote/path", "/local/path")
    server.close()

    (with statement support)
    with sftp.Server("user", "pass", "example.com") as server:
        server.upload("/local/path", "/remote/path")
"""

import paramiko

class Server(object):
    """
    Wraps paramiko for super-simple SFTP uploading and downloading.
    """

    def __init__(self, username, password, host, port=22):

        self.transport = paramiko.Transport((host, port))
        self.transport.connect(username=username, password=password)
        self.sftp = paramiko.SFTPClient.from_transport(self.transport)

    def upload(self, local, remote):
        self.sftp.put(local, remote)

    def download(self, remote, local):
        self.sftp.get(remote, local)

    def close(self):
        """
        Close the connection if it's active
        """

        if self.transport.is_active():
            self.sftp.close()
            self.transport.close()

    # with-statement support
    def __enter__(self):
        return self

    def __exit__(self, type, value, tb):
        self.close()
