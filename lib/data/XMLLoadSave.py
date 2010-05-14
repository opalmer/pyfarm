'''
HOMEPAGE: www.pyfarm.net
INITIAL: May 15 2009
PURPOSE: Module used to read and write XML files for the que

    This file is part of PyFarm.
    Copyright (C) 2008-2010 Oliver Palmer

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
import lib.Logger as logger
from PyQt4.QtCore import QIODevice, QFile, QDateTime
from PyQt4.QtCore import QString, QXmlStreamWriter, QXmlStreamReader

__MODULE__ = "lib.data.XMLLoadSave"

class SaveQueToXML(object):
    '''
    Parse the given job information and save
    it to an output file when asked.
    '''
    def __init__(self, data):
        self.data = {}
        for key in data.keys():
            self.data[key] = data[key].jobData()

    def save(self, output):
        '''Save the data to the given output file'''
        # inital setup
        out = QString()
        xml = QXmlStreamWriter(out)
        xml.setAutoFormatting(1)
        xml.writeStartDocument()
        xml.writeStartElement("Jobs")

        # write outer job elements
        for job in self.data.keys():
            data = self.data[job]
            xml.writeStartElement("job")
            xml.writeAttribute("name", job)
            for subjob in data.keys():
                xml.writeStartElement("subjob")
                # write subjob info
                xml.writeAttribute("subid", subjob)
                xml.writeAttribute("status", str(data[subjob]["status"]))
                xml.writeAttribute("priority", str(data[subjob]["priority"]))

                # write statistics info
                fStats = data[subjob]["statistics"]["frames"]
                frameInfo = {"avgTime": fStats["avgTime"],
                                    "complete": fStats["complete"],
                                    "failed": fStats["failed"],
                                    "frameCount": fStats["frameCount"],
                                    "maxTime": fStats["maxTime"],
                                    "minTime": fStats["minTime"],
                                    "rendering": "0",
                                    "waiting": fStats["waiting"]+fStats["rendering"]
                                    }
                xml.writeStartElement("statistics")
                xml.writeStartElement("frames")
                for key, value in frameInfo.items():
                    xml.writeAttribute(key, str(value))
                xml.writeEndElement()
                xml.writeEndElement()

                # write individual frame data
                for frame in data[subjob]["frames"]:
                    xml.writeStartElement("entry")
                    xml.writeAttribute("num", str(frame))
                    for entry in data[subjob]["frames"][frame]:
                        dataDict = entry[1]
                        xml.writeAttribute("id", entry[0])
                        if dataDict["status"] == 1:
                            xml.writeAttribute("status", "0")
                        else:
                            xml.writeAttribute("status", str(dataDict["status"]))

                        if type(dataDict["start"]) == int:
                            start = dataDict["start"]
                        else:
                            start = dataDict["start"].toTime_t()

                        if type(dataDict["end"]) == int:
                            end = dataDict["end"]
                        else:
                            end = dataDict["end"].toTime_t()
                        xml.writeAttribute("log", dataDict["log"])
                        xml.writeAttribute("pid", str(dataDict["pid"]))
                        xml.writeAttribute("elapsed", str(dataDict["elapsed"]))
                        xml.writeAttribute("start", str(start))
                        xml.writeAttribute("end", str(end))
                        xml.writeAttribute("host", str(dataDict["host"]))
                        xml.writeAttribute("software", dataDict["software"])
                        xml.writeAttribute("command", dataDict["command"])

                    # close elements
                    xml.writeEndElement()
                xml.writeEndElement()
            xml.writeEndElement()

        # write end elements then write to disc
        xml.writeEndDocument()
        f = open(output, 'w')
        f.write(out)
        f.close()

class LoadQueFromXML(object):
    '''
    Parse the input xml file and merge it
    with the current job information
    '''
    def __init__(self, parentClass):
        self.data = parentClass.dataJob
        self.createJob = parentClass.submitJob.submitJob
        self.addFromXML = parentClass.submitJob.addFromXML

    def getAttr(self, attr):
        value = self.xml.attributes().value(attr).toString()
        return str(self.xml.attributes().value(attr).toString())

    def load(self, input):
        '''Load the job data from the given input file'''
        xmlIO = QFile(input)
        xmlIO.open(QIODevice.ReadOnly)
        self.xml = QXmlStreamReader(xmlIO)
        while not self.xml.atEnd():
            self.xml.readNext()
            if self.xml.tokenType() == QXmlStreamReader.StartElement:
                element = self.xml.name().toString()
                # job processing
                if element == "job":
                    job = self.getAttr("name")

                # subjob processing
                elif element == "subjob":
                    subjob = {"subid" : self.getAttr("subid"),
                                    "status" : self.getAttr("status"),
                                    "priority" : self.getAttr("priority")}
                    if job not in self.data:
                        self.createJob(job, subjob["priority"], subjob["status"])

                # frame statistics processing
                elif element == "frames":
                    statistics = {"waiting" : self.getAttr("waiting"),
                                        "complete" : self.getAttr("complete"),
                                        "rendering" : self.getAttr("rendering"),
                                        "frameCount" : self.getAttr("frameCount"),
                                        "failed" : self.getAttr("failed"),
                                        "minTime" : self.getAttr("minTime"),
                                        "maxTime" : self.getAttr("maxTime"),
                                        "avgTime" : self.getAttr("avgTime")}

                # entry processing
                elif element == "entry":
                    frame = {"num" : int(self.getAttr("num")),
                                "id" : self.getAttr("id"),
                                "status" : int(self.getAttr("status")),
                                "log" : self.getAttr("log"),
                                "pid" : self.getAttr("pid"),
                                "elapsed" : int(self.getAttr("elapsed")),
                                "start" : QDateTime().fromTime_t(int(self.getAttr("start"))),
                                "end" : QDateTime().fromTime_t(int(self.getAttr("end"))),
                                "host" : self.getAttr("host"),
                                "software" : self.getAttr("software"),
                                "command" : self.getAttr("command")}

                    # add the data to the job dictionary
                    self.addFromXML(job, subjob["subid"], subjob["status"], subjob["priority"], frame["num"], frame["id"],
                                        frame["status"], frame["log"], frame["pid"], frame["elapsed"], frame["start"],
                                        frame["end"], frame["host"], frame["software"], frame["command"])

