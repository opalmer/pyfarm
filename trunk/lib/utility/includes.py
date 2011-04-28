'''
HOMEPAGE: www.pyfarm.net
INITIAL: April 27 2011
PURPOSE: Package level utilities

This file is part of PyFarm.
Copyright (C) 2008-2011 Oliver Palmer

PyFarm is free software: you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

PyFarm is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public License
along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.
'''

import copy


# TODO: Expand to allow solution to iterate over keys/values
# TODO: Expand to allow access by index
# TODO: Repimplement __repr__, __str__, __len__, __cmp__
# TODO: Store keys/values in a dictionary to prevent changes once the enum
#       has been constructed
def Enum(*static, **assigned):
    '''
    Enumerated object generator.

    Static enums have interger values which are determined by their
    input position.  Unlike assigned enums they do not require extra arguments,
    have a small memory footprint, and are fast to compute.

    Assigned enums on the other hand offer greater flexibility at the cost of
    higher ram and processing requirements due to each value being copied to
    its respective key (see TODO list above)
    '''
    # make sure we are not try to provide two inputs
    if static and assigned:
        raise Exception("You cannot use both static and assigned enums")

    # make sure we have at least one input
    elif not static and not assigned:
        raise Exception("Enum function requires input")

    class EnumClass(object): pass
    enumObj = EnumClass()

    def validKey(name):
        '''
        Return True if the first letter of given name matches [A-Za-z]
        and is alphanumeric
        '''
        if hasattr(enumObj, name): # this only works for static enums
            raise Exception("You cannot use the same key twice")

        if name[0].isalpha() and name.isalnum():
            return True
        return False

    # static values
    if static:
        for value in range(len(static)):
            key = static[value]
            if not validKey(key):
                continue

            setattr(enumObj, key, value)

    # assigned values
    else:
        for key, value in assigned.items():
            key = str(key)
            if not validKey(key):
                continue

            setattr(enumObj, key, copy.deepcopy(value))



    return enumObj
