'''
AUTHOR: Oliver Palmer
CONTACT: opalme20@student.scad.edu || (703)725-6544
INITIAL: Dec 8 2008
PURPOSE: This module is intended to help install and update PyFarm both
at the system level and the program level.
'''

# TODO: Create cross-platform installer
class SystemPackages(object):
    '''
    Used to install a grou of packages onto the local system

    INPUT FORMAT OF PACKAGE STRING:
        group:subgroup:system_package

    VARS:
        pkgList (string) -- package list, includes path
        group (string) -- group to search for in packages
        subgroup (string) -- subgroup to search for in packages
        simulate (boolean) -- if true, do not install only print
    '''
    def __init__(self, pkgList, group, subgroup, simulate=False):
        self.pkgList = pkgList
        self.group = group
        self.subgroup = subgroup
        self.simulate = simulate

    def install(self):
        '''
        Echo the package instead of installing it

        INPUT:
            self.pkgprefix
            self.content
            self.subcontent

        RETURN:
            The packages to install (strings)
        '''
        import os
        from subprocess import call,PIPE

        for line in open(self.pkgList, 'r').readlines():
            if line[0] == '#':
                pass # becuase we don't want to process commented lines
            else:
                if line.split('\n')[0].split(':')[0] == self.group and line.split('\n')[0].split(':')[1] == self.subgroup:
                    package = line.split('\n')[0].split(':')[2]
                    if self.simulate:
                        # if self.simulate == True; just echo the packages
                        print 'Now installing: %s' % package
                    else:
                        if call('dpkg -s %s' % package,shell=True,stdout=PIPE,stderr=PIPE): # if not installed
                            p = Popen('sudo apt-get -y install %s' % package,shell=True,stdout=PIPE,stderr=PIPE)

                            while True:
                                o = p.stdout.readline()
                                print o.split('\n')[0]

                                if o == '' and p.poll() != None:
                                    break
                               # the 'o' variable stores a line from the command's stdout
                               # do anything u wish with the 'o' variable here
                               # this loop will break once theres a blank output
                               # from stdout and the subprocess have ended

                        else:
                            print "%s is already installed" % package


class PyFarm(object):
    '''Used to add updates or install PyFarm'''
    def __init__(self):
        pass

    def install(self):
        '''Install PyFarm given the source code.  Could also be used to update code'''
        pass

    def update(self):
        '''Update PyFarm to the latest version'''
        pass
