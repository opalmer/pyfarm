# No shebang line, this module is meant to be imported
#
# INITIAL: June 19 2011
# PURPOSE: To format and return strings
#
# This file is part of PyFarm.
# Copyright (C) 2008-2011 Oliver Palmer
#
# PyFarm is free software: you can redistribute it and/or modify
# it under the terms of the GNU Lesser General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# PyFarm is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public License
# along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.

import re
import time
import string

class Template(object):
    '''Basic template to generate outout strings'''
    def __init__(self, template):
        self.template = template

    def __timestamp(self):
        '''Return a timestamp using the proper format'''
        timeTemplate = self.template.get("Timestamp", {})
        timeFormat = timeTemplate.get("format", "%D %T")
        return time.strftime(timeFormat)

    def sub(self, data, removeMissing=True):
        '''
        Replace items in the template with variables in replace.  If
        removeMissing is True then remove any unmathed string in the
        template.

        >>> template = StringTemplate("$hello $world")
        >>> template.sub({"hello" : "hello"))
        'hello'
        '''
        template = self.template['Output']['format']

        # find all template strings in the template
        for templateString in re.findall("([$]\w+[.]\w+)", template):

            # break the match down into a list: [key, value]
            key, entry = templateString.replace("$", "").split(".")
            section = data.get(key)

            # retrieve the timestamp (will ALWAYS return a timestamp
            # even if the config file does not contain the time format
            if key == "Timestamp":
                value = self.__timestamp()

            if section:
                value = section.get(entry)

            # if a value was found replace the template string
            if value:
                template = template.replace(templateString, value)

            # otherwise if removeMissing is True remove the template
            # string
            elif not value and removeMissing:
                template = template.replace(templateString, "")

        return template

if __name__ == '__main__':
    import config
    template = Template(config.FORMAT_DATA)
    data = {
        "input" : {
            "message" : "hello world",
            "level" : "test level"
            }
            }

    for i in range(120):
        data["input"]["message"] = "hi %i" % i