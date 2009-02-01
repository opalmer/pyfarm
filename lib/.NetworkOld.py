'''
AUTHOR: Oliver Palmer
CONTACT: opalme20@student.scad.edu || (703)725-6544
INITIAL: Feb 1 2008
PURPOSE: Network modules used to hold older, unused, network modules
'''

class TCPServer(QTcpServer):
    '''Threaded CP Server used to handle incoming requests'''
    def __init__(self, parent=None):
        super(TCPServer, self).__init__(parent)

    def incomingConnection(self, socketid):
        '''If a new connection is found, start a thread for it'''
        thread = TCPServerThread(socketid, self)
        print "TCPServer() - DEBUG - Got incoming connection at %s" % socketid
        self.connect(thread, SIGNAL("finished()"), thread, SLOT("deleteLater()"))
        thread.start()


class TCPClient(QTcpSocket):
    '''TCP Client to communicate with TCP server'''
    def __init__(self, host="localhost", port=TCP_PORT, parent=None):
        super(TCPClient, self).__init__(parent)
        self.host = host
        self.port = port
        self.socket = QTcpSocket(parent)
        self.nextBlockSize = 0
        self.request = None

        # setup the connections
        self.connect(self.socket, SIGNAL("connected()"), self.sendRequest)
        self.connect(self.socket, SIGNAL("readyRead()"), self.readResponse)
        self.connect(self.socket, SIGNAL("disconnected()"), self.serverHasStopped)
        self.connect(self.socket, SIGNAL("error(QAbstractSocket::SocketError)"), self.serverHasError)

    def pack(self, action, software, options, job, frame):
        '''
        Pack the information into a packet

        VARS:
            action (string) - action to perform
                    +render - render the give frame
                    +status - current status of the render
                        - waiting
                        - running
                        - failed
                    +kill - if rendering, STOP
                    +log - if rendering, tail the process log
            software (string) - software to render with
            options (string) - string of render options
            job (string) - job NUMBER
            frame (string) - frame to render, query, etc.
        '''
        self.request = QByteArray()
        stream = QDataStream(self.request, QIODevice.WriteOnly)
        stream.setVersion(QDataStream.Qt_4_2)
        stream.writeUInt16(0)

        # pack the data
        stream << action << software << options << job << frame
        stream.device().seek(0)
        stream.writeUInt16(self.request.size() - SIZEOF_UINT16)

        # if the socket is open, close it
        if self.socket.isOpen():
            self.socket.disconnectFromHost()

        # once the socket emits connected() self.sendRequest is called
        self.socket.connectToHost(self.host, self.port)

    def sendRequest(self):
        '''Send the packed packet'''
        self.nextBlockSize = 0
        self.socket.write(self.request)
        self.request = None

    def readResponse(self):
        '''Read the response from the server'''
        stream = QDataStream(self.socket)
        stream.setVersion(QDataStream.Qt_4_2)

        # while there is streaming data
        while True:
            if self.nextBlockSize == 0:
                if self.socket.bytesAvailable() < SIZEOF_UINT16:
                    break
                self.nextBlockSize = stream.readUInt16()
            if self.socket.bytesAvailable() < self.nextBlockSize:
                break

            action = QString()
            software = QString()
            options = QString()
            job = QString()
            frame = QString()
            scene = QString()

            stream >> action >> job >> frame
            if action == "ERROR":
                msg = QString("Error: %1").arg(command)
            elif action == "RENDER":
                msg = QString("Rendering frame %2 of job %1").arg(job).arg(frame)
                self._updateStatus('TCPServer', msg, 'green')
            self.nextBlockSize = 0

    def serverHasStopped(self):
        '''If the server has stopped or been shutdown, close the socket'''
        self.socket.disconnectFromHost()

    def serverHasError(self, error):
        '''Gather errors then close the connection'''
        print QString("Error: %1").arg(self.socket.errorString())
        self.socket.disconnectFromHost()


class TCPServerStdOutThread(QThread):
    '''
    Threaded TCP Server used to handle all incoming
    standard output information.
    '''
    lock = QReadWriteLock()
    def __init__(self, socketid, parent):
        super(TCPServerStdOutThread, self).__init__(parent)
        self.socketid = socketid

    def run(self):
        '''Start the server'''
        #print "TCPServerStdOutThread() - DEBUG - Started Thread"
        socket = QTcpSocket()
        #socket.setReadBufferSize(500000000)
        #socket.setWriteBufferSize(500000000)
        print "Running thread"

        if not socket.setSocketDescriptor(self.socketid):
            print "setSocketDescriptor(%s) is NOT 1" % self.socketid
            self.emit(SIGNAL("error(int)"), socket.error())
            return

        # while we are connected, do this
        while socket.state() == QAbstractSocket.ConnectedState:
            nextBlockSize = 0
            stream = QDataStream(socket) # the stream is a QDataStream
            stream.setVersion(QDataStream.Qt_4_2) # set the version of the stream
            while True:
                #try:
                    #TCPServerStdOutThread2.lock.lockForRead()
                socket.waitForReadyRead(-1)
                if socket.bytesAvailable() >= SIZEOF_UINT16:
                    nextBlockSize = stream.readUInt16()
                    job = QString()
                    frame = QString()
                    stdout = QString()
                    host = QString()
                    output = QStringList()
                    stream >> job >> frame >>  host >> stdout
                    for arg in (job, frame, host, stdout):
                        output.append(arg)

                    self.emit(SIGNAL("emitStdOutLine"), output)

            if socket.bytesAvailable() < nextBlockSize:
                while True:
                    socket.waitForReadyRead(-1)
                    if socket.bytesAvailable() >= nextBlockSize:
                       pass

        socket.close()


class TCPServerStdOut(QTcpServer):
    '''Threaded CP Server used to handle incoming requests'''
    def __init__(self, parent=None):
        super(TCPServerStdOut, self).__init__(parent)

    def incomingConnection(self, socketid):
        '''If a new connection is found, start a thread for it'''
        print "Incoming Connection"
        thread = TCPServerStdOutThread2(socketid, self)
        self.connect(thread, SIGNAL("emitStdOutLine"), self.emitLine)
        self.connect(thread, SIGNAL("finished()"), thread, SLOT("deleteLater()"))
        thread.start()

    def emitLine(self, line):
        self.emit(SIGNAL("emitStdOutLine"), line)
