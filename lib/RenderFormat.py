'''
AUTHOR: Oliver Palmer
CONTACT: opalme20@student.scad.edu || (703)725-6544
INITIAL: Dec 15 2008
PURPOSE: Used to format commands sent to the system.  For example, when
the users requests to render from maya with mental ray then this class
will inform PyFarm to use the -r mr flag along with the Render command.
'''

# TODO: Add 'default' options to RenderFormat
class Maya(object):
    '''Format GUI output for rendering with maya'''
    def __init__(self, scene):
        self.scene = scene
