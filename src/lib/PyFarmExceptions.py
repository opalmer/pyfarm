'''
HOMEPAGE: www.pyfarm.net
INITIAL: April 4, 2009
PURPOSE: Module used to handle error processing for PyFarm

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

__MODULE__ = "lib.PyFarmExceptions"

class UnexpectedType(Exception):
    '''
    If given an unexpected type for a parameter, throw an
    exception.

    INPUT:
        module (str) -- the module or location of error generation
        expected (str) -- the type expected
        given (str) -- value given by user
    '''
    def __init__(self, module, expected, given):
        self.module = module
        self.expected = expected
        self.given = given

    def __str__(self):
        return repr('%s was expecting a %s not a %s type' % (self.module, self.expected, self.given))


class UnexpectedValue(Exception):
    '''
    If given an unexpected value for a parameter, throw an
    exception

    INPUT:
        module (str) -- the module or location of error generation
        sRange (int) -- start of value range
        eRange (int) -- end of value range
        given (int) -- value given by user
    '''
    def __init__(self, module, sRange, eRange, given):
        self.module = module
        self.sRange = sRange
        self.eRange = eRange
        self.given = given

    def __str__(self):
        return repr('%s was expecting a value between %s and %s, %s is outside of that range'\
                    % (self.module, self.sRange, self.eRange, self.given))


class UnexpectedString(Exception):
    '''
    If given an unexpected string for a parameter, throw an
    exception

    INPUT:
        module (str) -- the module or location of error generation
        expected (str) -- the expected value
        given (str) -- the value given by user
    '''
    def __init__(self, module, expected, given):
        self.module = module
        self.expected = expected
        self.given = given

    def __str__(self):
        return repr('%s was expecting %s, the string %s was not expected'\
                    % (self.module, self.expected, self.given))


class XMLFormattingError(Exception):
    '''
    If given a poorly formatted xml file, raise
    an exception

    INPUT:
        module (str) -- the module or location of error generation
        error (str) -- the original exception by xml.parsers
    '''
    def __init__(self, module, error):
        self.module = module
        self.error = error

    def __str__(self):
        return repr('%s could not parse the xml file, original exception was: %s' % (self.module, self.error))


class XMLKeyError(Exception):
    '''
    If given a key error from an xml file,
    raise an exception.

    INPUT:
        key(str) -- the original key that was searched
    '''
    def __init__(self, key):
        self.key = key

    def __str__(self):
        return repr('%s is not a valid key in settings.xml' % self.key)


class XMLSoftwareList(Exception):
    '''
    If skipSoftware=True and the key is request, throw
    this exception

    INPUT:
        doc (str) -- the xml document
    '''
    def __init__(self, doc):
        self.doc = doc

    def __str__(self):
        return repr('%s does not contain the attribute "softwareList", please check the \
        skipSoftware input value' % (self.doc))


class ErrorProcessingSetup(object):
    '''
    Main class meant to setup error processing

    module (str) -- name of main module for error processing
    '''
    def __init__(self, module):
        self.module = module

    def stringError(self, expected, given):
        '''Return a string error'''
        return UnexpectedString(self.module, expected, given)

    def typeError(self, expected, given):
        '''Return a type error'''
        return UnexpectedType(self.module, expected, given)

    def valueError(self, sRange, eRange, given):
        '''Return a value error'''
        return UnexpectedValue(self.module, sRange, eRange, given)

    def xmlFormattingError(self, doc, error):
        '''Return an xml formatting error'''
        return XMLFormattingError(doc, error)

    def xmlKeyError(self, key):
        '''Return an xml key error'''
        return XMLKeyError(key)

    def xmlSkipSoftwareValue(self, doc):
        '''Return an xml software value key error'''
        return XMLSoftwareList(doc)
