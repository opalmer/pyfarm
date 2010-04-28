'''
HOMEPAGE: www.pyfarm.net
INITIAL: April 24 2009
PURPOSE: Manages and updates the job details table

    This file is part of PyFarm.
    Copyright (C) 2008-2009 Oliver Palmer

    PyFarm is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    PyFarm is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.
'''
# From PyQt
from PyQt4.QtGui import QColor, QPushButton
from PyQt4.QtCore import Qt, QString, QAbstractTableModel, QModelIndex, QVariant, QTime

# From PyFarm
import lib.Logger as logger
from lib.Info import Int2Time
from lib.ReadSettings import ParseXmlSettings

__MODULE__ = "lib.ui.main.job.table.JobDetailsTableModel"

settings = ParseXmlSettings('./cfg/settings.xml', skipSoftware=True)
SUBJOB, STATUS, UID, FRAME, START, END, ELAPSED, HOST, PID, SOFTWARE, COMMAND = range(11)

class FrameEntry(object):
    def __init__(self, subjob, status, uid, framenum, start, end, elapsed, host, pid, software, command):
        self.subjob = QString(subjob)
        self.status = status
        self.uid = QString(uid)
        self.framenum = QString().setNum(framenum)
        self.start = start
        self.end = end
        self.elapsed = QString("%sd %sh %sm %ss" % (Int2Time(elapsed)[0], Int2Time(elapsed)[1], Int2Time(elapsed)[2], Int2Time(elapsed)[3]))
        self.host = QString(self._ifNone(host))
        self.pid = QString(self._ifNone(pid))
        self.software = QString(software)
        self.command = QString(command)

    def _ifNone(self, value):
        '''If value is none return an equivalent string'''
        if value == None:
            return "None"
        else:
            return value

    def __cmp__(self, other):
        QString.localeAwareCompare(self.status.toLower(),
                                          other.status.toLower())


class JobDetailsTableModel(QAbstractTableModel):
    def __init__(self, dataDict, parent):
        super(JobDetailsTableModel, self).__init__()
        self.parent = parent
        self.table = parent.ui.frameTable
        self.dataDict = dataDict.jobData()
        self.loadData()

    def log(self, subjob, frame, id):
        '''Yield the log lines for the given subjob, frame, and id'''
        log = self.dataDict[str(subjob)]["frames"][int(frame[0])]
        for entry in log:
            if entry[0] == str(id):
                for line in entry[1]["log"]:
                    yield line

    def sortByStatus(self):
        '''Sort the table by status'''
        def compare(a, b):
            if a.status != b.status:
                return QString.localeAwareCompare(a.status, b.status)
            if a.status != b.status:
                return QString.localeAwareCompare(a.status, b.status)
            return QString.localeAwareCompare(a.status, b.status)
        self.frames = sorted(self.frames, compare)
        self.reset()

    def sortByElapsed(self):
        '''Sort the table by elapsed time'''
        def compare(a, b):
            if a.elapsed != b.elapsed:
                return QString.localeAwareCompare(a.elapsed, b.elapsed)
            if a.elapsed != b.elapsed:
                return QString.localeAwareCompare(a.elapsed, b.elapsed)
            return QString.localeAwareCompare(a.elapsed, b.elapsed)
        self.frames = sorted(self.frames, compare)
        self.reset()

    def refreshStats(self, table):
        '''Refresh the gui stats section'''
        statusCount = {}
        for subjob in self.dataDict:
            for frame in self.dataDict[subjob]["frames"]:
                for job in self.dataDict[subjob]["frames"][frame]:
                    status = job[1]["status"]
                    if status not in statusCount:
                        statusCount[status] = 1
                    else:
                        statusCount[status] += 1

    def loadData(self):
        '''Load the data'''
        self.frames = []
        # get the frames and other info
        #  then add it to self.frames
        for subjob in self.dataDict.keys():
            for frame in self.dataDict[subjob]["frames"]:
                for entry in self.dataDict[subjob]["frames"][frame]:
                    info = entry[1]
                    self.frames.append(FrameEntry(subjob, info["status"], entry[0], frame, info["start"],
                        info["end"], info["elapsed"], info["host"], info["pid"], info["software"], info["command"]))

    def data(self, index, role=Qt.DisplayRole):
        if not index.isValid() or \
           not (0 <= index.row() < len(self.frames)):
            return QVariant()
        frame = self.frames[index.row()]
        column = index.column()
        if role == Qt.DisplayRole:
            # populate the column data
            if column == SUBJOB:
                return QVariant(frame.subjob)
            elif column == STATUS:
                return settings.frameStatus(frame.status)
            elif column == UID:
                return QVariant(frame.uid)
            elif column == FRAME:
                return QVariant(frame.framenum)
            elif column == START:
                return QVariant(frame.start)
            elif column == END:
                return QVariant(frame.end)
            elif column == ELAPSED:
                return QVariant(frame.elapsed)
            elif column == HOST:
                return QVariant(frame.host)
            elif column == PID:
                return QVariant(frame.pid)
            elif column == SOFTWARE:
                return QVariant(frame.software)
            elif column == COMMAND:
                return QVariant(frame.command)

        elif role == Qt.TextAlignmentRole:
            if column != COMMAND:
                return QVariant(int(Qt.AlignCenter|Qt.AlignVCenter))
            return QVariant(int(Qt.AlignLeft|Qt.AlignVCenter))
        # set the text color
        elif role == Qt.TextColorRole and column == STATUS:
            if frame.status in range(0, 4):
                return settings.fgColor(frame.status)

        # set the background color
        elif role == Qt.BackgroundColorRole:
            if frame.status in range(0, 4):
                return settings.bgColor(frame.status)
        return QVariant()

    def headerData(self, section, orientation, role=Qt.DisplayRole):
        if role == Qt.TextAlignmentRole:
            if orientation == Qt.Horizontal:
                return QVariant(int(Qt.AlignLeft|Qt.AlignVCenter))
            return QVariant(int(Qt.AlignRight|Qt.AlignVCenter))
        if role != Qt.DisplayRole:
            return QVariant()
        if orientation == Qt.Horizontal:
            # setup the column names
            if section == SUBJOB:
                return QVariant("Subjob")
            elif section == STATUS:
                return QVariant("Status")
            elif section == UID:
                return QVariant("UID")
            elif section == FRAME:
                return QVariant("Frame")
            elif section == START:
                return QVariant("Start")
            elif section == END:
                return QVariant("End")
            elif section == ELAPSED:
                return QVariant("Elapsed")
            elif section == HOST:
                return QVariant("Host")
            elif section == PID:
                return QVariant("PID")
            elif section == SOFTWARE:
                return QVariant("Software")
            elif section == COMMAND:
                return QVariant("Command")

        return QVariant(int(section + 1))

    def rowCount(self, index=QModelIndex()):
        return len(self.frames)

    def columnCount(self, index=QModelIndex()):
        return 11
