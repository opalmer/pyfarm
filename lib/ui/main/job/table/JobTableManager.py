'''
HOMEPAGE: www.pyfarm.net
INITIAL: April 12 2009
PURPOSE: Classes for managing the main job table

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
# From PyQt4
from PyQt4.QtCore import QTimer, QThread
from PyQt4.QtCore import QString, QObject, SIGNAL, QRect, QPoint, QSize
from PyQt4.QtGui import QProgressBar, QDialog, QMainWindow, QBrush, QColor
from PyQt4.QtGui import QTableWidgetItem, QTableWidgetSelectionRange, QPushButton

# From PyFarm
from lib.Logger import Logger
from lib.ui.JobDetails import Ui_JobDetails
from lib.ui.LogViewer import Ui_LogViewer
from lib.ReadSettings import ParseXmlSettings
from lib.ui.main.job.table.JobDetailsTableModel import JobDetailsTableModel

__MODULE__ = "lib.ui.main.job.table.JobTableManager"
__LOGLEVEL__ = 4

settings = ParseXmlSettings('./cfg/settings.xml',  'cmd',  skipSoftware=True)
SUBJOB, STATUS, UID, FRAME, START, END, ELAPSED, HOST, PID, SOFTWARE, COMMAND = range(11)

class JobStatusObject(object):
    '''Object to hold various values related to a jobs status'''
    def __init__(self, job, data):
        status_value = data.status.overall()
        self.job = job

        # status text and coloring
        self.status = QString(settings.lookupStatus(status_value))
        self.fgColor = QBrush(settings.fgColor(status_value))
        self.bgColor = QBrush(settings.bgColor(status_value))


class LogViewer(QMainWindow):
    '''Log viewer subclass'''
    def __init__(self, job, data, subjob, frame, id, host, parent=None):
        super(LogViewer, self).__init__(parent)
        # initial var setup
        self.loggedLines = []
        self.job = job
        self.data = data[job].data
        self.subjob = str(subjob)
        self.frame = frame[0]
        self.id = str(id)
        self.host = host


        # gui setup
        self.ui = Ui_LogViewer()
        self.ui.setupUi(self)

        # add widgets to status bar
        statusbarWidgets = [self.ui.refreshNow, self.ui.autoRefresh,
                                        self.ui.refreshTime, self.ui.deleteLog,
                                        self.ui.saveLog, self.ui.closeButton]

        for widget in statusbarWidgets:
            self.ui.statusbar.addPermanentWidget(widget)

        self.connect(self.ui.refreshNow, SIGNAL("clicked()"), self.updateLog)
        self.setWindowTitle(QString("Subjob: %s Frame: %i Subframe: %s Host: %s" % (self.subjob, self.frame, self.id, self.host)))

        # setup refresh timer
        self.refreshTimer = QTimer()
        self.refreshTimer.setInterval(self.ui.refreshTime.value()*1000)
        self.connect(self.ui.autoRefresh, SIGNAL("stateChanged(int)"), self.setAutoRefresh)
        self.connect(self.ui.refreshTime, SIGNAL("valueChanged(int)"), self.setRefreshInterval)
        self.connect(self.refreshTimer, SIGNAL("timeout()"), self.updateLog)

        # finally, source the initial log
        self.sourceLog()

    def resizeEvent(self, event):
        '''Resize the internal widgets when the main gui is resized'''
        sizeOld = event.oldSize()
        sx = event.size().width()
        sy = event.size().height()
        sizeNew = QSize(sx-40, sy-95)
        sizeNewHeader = QSize(sx-140, 25)

        # finally, resize the components
        self.ui.log.resize(sizeNew)
        self.ui.header.resize(sizeNewHeader)

    def setRefreshInterval(self, interval):
        '''Take an input refresh interval and set a new timer interval'''
        self.refreshTimer.setInterval(interval*1000)

    def setAutoRefresh(self, state):
        '''Initial start of auto refreshing of logs'''
        self.ui.refreshTime.setEnabled(state)
        if state:
            self.refreshTimer.start()
        else:
            self.refreshTimer.stop()

    def log(self):
        '''Yield the log lines for the given subjob, frame, and id'''
        for line in open(self.data.frame.getFrame(self.subjob, self.frame, self.id)["log"], 'r'):
            yield QString(line).trimmed()

    def appendLine(self, line):
        '''Append a line to gui and internal data'''
        self.ui.log.append(line)
        self.loggedLines.append(line)

    def sourceLog(self):
        '''Source the given log and add it to the view'''
        for line in self.log():
            self.appendLine(line)

    def updateLog(self):
        for line in self.log():
            if line not in self.loggedLines:
                self.appendLine(line)


class JobDetails(QMainWindow):
    def __init__(self, job, data, parentClass, parent=None):
        super(JobDetails, self).__init__(parent)
        self.parent = parent
        # create the table
        self.refreshInterval = 5
        self.ui = Ui_JobDetails()
        self.ui.setupUi(self)

        # add widgets to status bar
        statusbarWidgets = [self.ui.refreshNow, self.ui.autoRefresh,
                                        self.ui.refreshTime, self.ui.openLog,
                                        self.ui.closeButton]
        for widget in statusbarWidgets:
            self.ui.statusbar.addPermanentWidget(widget)

        self.model = JobDetailsTableModel(data[job], self)
        self.table = self.ui.frameTable
        self.table.setModel(self.model)
        self.resizeColumns()
        self.modName = 'JobTableManager.JobDetails'
        self.job = job
        self.data = data

        # create a timer to refresh the data with
        self.refreshTimer = QTimer()
        self.refreshTimer.setInterval(self.refreshInterval*1000)

        # setup the refresh actions
        header = self.table.horizontalHeader()
        self.connect(header, SIGNAL("sectionClicked(int)"), self.sortTable)
        self.connect(self.ui.autoRefresh, SIGNAL("stateChanged(int)"), self.setAutoRefreshState)
        self.connect(self.ui.refreshTime, SIGNAL("valueChanged(int)"), self.setRefreshInterval)
        self.connect(self.ui.refreshNow, SIGNAL("pressed()"), self.refreshUi)
        self.connect(self.refreshTimer, SIGNAL("timeout()"), self.refreshUi)

        # setup log view actions
        self.connect(self.ui.openLog, SIGNAL("clicked()"), self.openLog)

    def resizeEvent(self, event):
        '''Resize the internal widgets when the main gui is resized'''
        sizeOld = event.oldSize()
        sx = event.size().width()
        sy = event.size().height()
        sizeNew = QSize(sx-150, sy-95)
        sizeNewHeader = QSize(sx-160, 25)

        # finally, resize the components
        self.table.resize(sizeNew)
        self.ui.header.resize(sizeNewHeader)

    def openLog(self):
        '''Open a log for viewing, row/col only here to prevent errors'''
        # prevent the user from attempting to open more than a single log
        indexes = self.table.selectedIndexes()
        if not len(indexes) > 11:
            for index in indexes:
                if index.column() == SUBJOB:
                    subjob = index.data().toString()
                elif index.column() == FRAME:
                    frame = index.data().toInt()
                elif index.column() == UID:
                    id = index.data().toString()
                elif index.column() == HOST:
                    host = index.data().toString()

            LogView = LogViewer(self.job, self.data, subjob, frame, id, host, self)
            LogView.show()
        else:
            self.parent.msg.warning('Cannot View More Than One Log', 'Sorry but you cannot view more than one log at a time.')
            log('PyFarm :: %s :: You cannot view more than one log at a time' % self.modName, 'warning')

    def sortTable(self, section):
        if section in (1, 6):
            if section == 1:
                self.model.sortByStatus()
                self.resizeColumns()
            elif section ==6:
                self.model.sortByElapsed()
                self.resizeColumns()
        else:
            log("PyFarm :: %s :: Sorting not setup for this section" % self.modName, 'debug')
            self.resizeColumns()

    def reject(self):
        '''Perform these actions when closing the ui'''
        self.ui.refreshTime.setEnabled(0)
        self.refreshTimer.stop()
        self.done(0)

    def refreshUi(self):
        '''Refresh the user interface with new data'''
        log("PyFarm :: %s :: Refreshing Table Data" % self.modName, 'debug')
        # Get the curretly selected row
        try:
            row = self.table.selectedIndexes()[0].row()
        except IndexError:
            row = None

        self.model = JobDetailsTableModel(self.data[self.job], self)
        self.table = self.ui.frameTable
        self.table.setModel(self.model)
        self.model.loadData()
        self.resizeColumns()

        # only reselect the row IF we had a selection
        if row != None:
            log("PyFarm :: %s :: Reselecting row %i" % (self.modName, row), 'debug')
            self.table.selectRow(row)

    def setRefreshInterval(self, interval):
        '''Set the new refresh interval'''
        self.refreshInterval = interval
        self.refreshTimer.setInterval(self.refreshInterval*1000)

    def setAutoRefreshState(self, isActive):
        '''Set the state of auto refresh'''
        if isActive:
            self.ui.refreshTime.setEnabled(isActive)
            self.refreshTimer.setInterval(self.refreshInterval*1000)
            self.refreshTimer.start()
        else:
            self.ui.refreshTime.setEnabled(isActive)
            self.refreshTimer.stop()

    def resizeColumns(self):
        for column in range(self.model.columnCount()):
            self.table.resizeColumnToContents(column)


class JobTableManager(QObject):
    '''Main job table manager'''
    def __init__(self, parentClass):
        super(JobTableManager, self).__init__(parentClass)
        self.ui = parentClass.ui
        self.parentClass = parentClass
        self.table = parentClass.ui.currentJobs
        self.connect(self.table, SIGNAL("cellEntered(int,int)"), self.cellEnteredAction)
        self.connect(self.table, SIGNAL("cellPressed(int,int)"), self.cellPressedAction)
        self.connect(self.table, SIGNAL("cellDoubleClicked(int,int)"), self.cellDoubleClicked)
        self.connect(self.table, SIGNAL("viewportEntered()"), self.viewportEnteredAction)
        self.connect(parentClass.ui.jobsDetails, SIGNAL("pressed()"), self.showJobDetails)
        self.connect(parentClass.ui.jobsRemove, SIGNAL("pressed()"), self.removeJob)
        self.jobNames = []
        self.jobs = parentClass.dataJob

        # Set some default vars
        self.clicked = 0
        self.cellPressed = 0

    def cellDoubleClicked(self, row, col):
        '''If a cell is double clicked, perform these actions'''
        self.cellPressedAction(row, col)
        self.showJobDetails()

    def cellPressedAction(self, row, col):
        '''Custom cell pressed action'''
        self.enableButtons()
        self.table.setRangeSelected(QTableWidgetSelectionRange(row, 0, row, 2), 1)
        self.row = row

    def cellEnteredAction(self, row, col):
        '''Custom cell entered action'''
        self.getJobStats(row)

    def viewportEnteredAction(self):
        '''Custom viewport entered action, when outside of cells'''
        self.zeroStats()

    def getJobStats(self, row):
        '''Get stats of the job that you recently rolled over'''
        statusDict = {self.ui.jobs_frames_waiting : self.jobs[self.currentJob(row)].status.waitingFrameCount(),
                      self.ui.jobs_frames_rendering : self.jobs[self.currentJob(row)].status.renderingFrameCount(),
                      self.ui.jobs_frames_complete : self.jobs[self.currentJob(row)].status.completeFrameCount(),
                      self.ui.jobs_frames_failed : self.jobs[self.currentJob(row)].status.failedFrameCount(),
                      self.ui.jobs_job_subjobs : self.jobs[self.currentJob(row)].status.subjobCount()}

        for key, value in statusDict.items():
            key.setNum(value)

    def deselectRow(self):
        '''Deselect the currently selected row'''
        try:
            self.table.setRangeSelected(self.table.selectedRanges()[0], 0)
        except IndexError:
            pass

    def zeroStats(self):
        '''Assign zeros to all items in the stats section'''
        statusDict = (self.ui.jobs_frames_waiting, self.ui.jobs_frames_rendering,
                            self.ui.jobs_frames_complete, self.ui.jobs_frames_failed,
                            self.ui.jobs_job_subjobs)

        for value in statusDict:
            value.setNum(0)

    def currentJob(self, row):
        '''Return the currently selected job name'''
        return str(self.table.item(row, 0).text())

    def resetTable(self):
        '''Reset the table to its original state'''
        self.zeroStats()
        self.cellPressed = 0
        self.disableButtons()
        self.deselectRow()

    def showJobDetails(self):
        '''Show job details for the selected job'''
        self.resetTable()
        self.details = JobDetails(self.currentJob(self.row), self.jobs, self, self.parentClass)
        self.details.show()

    def removeJob(self):
        self.resetTable()
        del self.jobs[self.currentJob(self.row)]
        self.jobNames.remove(self.currentJob(self.row))
        self.table.removeRow(self.row)
        self.zeroStats()
        self.deselectRow()

    def buttons(self):
        '''Yield each button back to the calling function'''
        buttons = [self.ui.jobsDetails, self.ui.jobsRemove, self.ui.jobsStop]
        for button in buttons:
            yield button

    def disableButtons(self):
        '''Disable all buttons'''
        for button in self.buttons():
            if button.isEnabled():
                button.setEnabled(0)

    def enableButtons(self):
        '''Enable the buttons when row is selected'''
        for button in self.buttons():
            if not button.isEnabled():
                button.setEnabled(1)

    def addJob(self, name):
        '''Add a job to the table'''
        if name not in self.jobNames:
            self.jobNames.append(name)

            # create the label widgets
            label = QTableWidgetItem(QString(name))
            status = QTableWidgetItem(QString("Waiting"))

            # set progress bar
            progress = QProgressBar()
            progress.setRange(0, 0)
            progress.setValue(0)

            # insert widgets
            row = self.table.rowCount()
            self.table.insertRow(row)
            self.table.setItem(row, 0, label)
            self.table.setItem(row, 1, status)
            self.table.setCellWidget(row, 2, progress)

    def _getJobRow(self, name):
        '''
        Return the row of the given job

        INPUT:
            name (str) --name of job
        '''
        row = 0
        for job in self.jobNames:
            if name == job:
                return row
            else:
                row += 1

    def addFrame(self, name):
        '''
        Add a frame to the progress bar for the given job
        and update other relevant values.

        INPUT:
            name (str) -- jobname to add the frame to
        '''
        row = self._getJobRow(name)
        progress = self.table.cellWidget(row, 2)
        progress.setMaximum(progress.maximum()+1)

    def updateJobObject(self, job):
        '''Update the status object to the most recent values'''
        if self.jobs[job].status.overall() in range(1, 4):
            self.job = None
            self.job = JobStatusObject(job, self.jobs[job])

    def setJobStatus(self, job):
        '''Set the status for the given job'''
        self.updateJobObject(job)
        row = self._getJobRow(job)
        # setup the status object
        status = self.table.item(row, 1)
        status.setText(self.job.status)
        status.setForeground(self.job.fgColor)
        status.setBackground(self.job.bgColor)

        # setup the main job name object
        name = self.table.item(row, 0)
        name.setForeground(self.job.fgColor)
        name.setBackground(self.job.bgColor)

    def frameFailed(self, job):
        '''
        Incriment the progress bar and other values

        INPUT:
            job (str) --- job to search for
        '''
        row = self._getJobRow(job)
        progress = self.table.cellWidget(row, 2)
        oldValue = progress.value()
        progress.setValue(oldValue+1)
        self.setJobStatus(job)

    def frameComplete(self, job):
        '''
        Incriment the progress bar and other values

        INPUT:
            job (str) --- job to search for
        '''
        row = self._getJobRow(job)
        progress = self.table.cellWidget(row, 2)
        oldValue = progress.value()
        progress.setValue(oldValue+1)
        self.setJobStatus(job)
