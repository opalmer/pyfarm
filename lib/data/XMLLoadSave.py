'''
HOMEPAGE: www.pyfarm.net
INITIAL: May 15 2009
PURPOSE: Module used to read and write XML files for the que

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
from PyQt4.QtCore import QString, QXmlStreamWriter

class SaveQueToXML(object):
    '''
    Parse the given job information and save
    it to an output file when asked.
    '''
    def __init__(self, data):
        self.data = data

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
            data = self.data[job].jobData()
            xml.writeStartElement("job")
            xml.writeAttribute("name", job)
            for subjob in data.keys():
                xml.writeStartElement("subjob")
                xml.writeAttribute("subid", subjob)
                for frame in data[subjob]["frames"]:
                    xml.writeStartElement("entry")
                    xml.writeAttribute("num", str(frame))
                    for entry in data[subjob]["frames"][frame]:
                        dataDict = entry[1]
                        xml.writeAttribute("id", entry[0])
                        xml.writeAttribute("status", str(dataDict["status"]))
                        log = ''
                        for line in dataDict["log"]:
                            log += '%s::' % line
                        xml.writeAttribute("log", log)
                        xml.writeAttribute("pid", str(dataDict["pid"]))
                        xml.writeAttribute("elapsed", str(dataDict["elapsed"]))
                        xml.writeAttribute("start", str(dataDict["start"]))
                        xml.writeAttribute("end", str(dataDict["end"]))
                        xml.writeAttribute("host", str(dataDict["host"]))
                        xml.writeAttribute("software", dataDict["software"])
                        xml.writeAttribute("command", dataDict["command"])
                    xml.writeEndElement()
                xml.writeEndElement()
            xml.writeEndElement()

        # write end elements
        xml.writeEndDocument()
        f = open(output, 'w')
        f.write(out)
        f.close()
