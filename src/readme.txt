PROGRAM
    PyFarm v0.3.215
    Released On August 6 2009

DEVELOPED BY
    Oliver Palmer

HOMEPAGE
    www.pyfarm.net

DESCRIPTION
    PyFarm is a render farm client written in Python and PyQt.  Currenly in the early stages
    of its lifespawn PyFarm seeks to support cross platform distributed rendering in a variety of
    software packges. Designed to be easy to configure and use PyFarm is meant to bridge the
    gap between production level pipeline render systems and a standard user interface.

SUPPORTED PLATFORMS
    Debian Linux Based Systems

SUPPORTED SOFTWARE
    Maya 8.0+

KNOWN ISSUES - MAJOR
	+ Adding a job from an external xml file does not set its proper status
	+ When shutting down the gui with active clients, not all clients shutdown/restart
	+ If a job finishes its status can be reset but clients still rendering
		- This problem is more superficial in that it does not prevent the job itself
		from finishing, just the gui from displaying the proper state

LICENSE
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

INSTALLATION INSTRUCTIONS
 1.) Open a console window and enter the command below (Note: The password will not be echoed back
     to you when you enter it.  This is done by the operating system for security reasons.)
        sudo aptitude -y install python-qt4

BASIC USAGE
    1.) Run the client program, Client.py
    2.) Run the main application, Main.pyw
    3.) Find nodes
    4.) Select a scene to render
    5.) Name the job
    6.) Submit
    7.) Render
