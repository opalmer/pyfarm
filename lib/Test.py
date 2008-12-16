'''
AUTHOR: Oliver Palmer
CONTACT: opalme20@student.scad.edu || (703)725-6544
INITIAL: Dec 15 2008
PURPOSE: Testing module, just for tests!
'''

class Internal:
    '''Class used to test internal class communication'''
    import sys
    def __init__(self, text):
        self.text = text

    def run(self, total):
        try:
            for i in range( int(total) ):
                print self.text

        except ValueError:
            sys.exit('\n[ ERROR ] Input to Internal.echo requires an integer')

    def echo(self, num):
        self.run(num)

    class Test:
        def main():
            return "It worked!"