PROGRAM
    PyFarm vRC3.193
    Released May 1 2009

DEVELOPED BY
    Oliver Palmer

HOMEPAGE
    www.pyfarm.net

DESCRIPTION
    PyFarm is  render farm client written in Python and PyQt.  Currenly in the early stages
    of its lifespawn PyFarm seeks to support cross platform distributed rendering in a variety of
    software packges. Designed to be easy to configure and use PyFarm is meant to bridge the
    gap between production level pipeline render systems and a standard user interface.

SUPPORTED PLATFORMS
    Debian Linux Based Systems

SUPPORTED SOFTWARE
    Maya 2008+

KNOWN ISSUES
    +Software is discovered but cannot be rendered from (Houdini and Shake)
    +When rendering from more than eight machines the interface lags
    +Main gui will not open if software is not discovered
    +Adding a job with an empty name will show a warning but still add the job
    +Please see the about release candidates page: http://www.pyfarm.net/wiki/doku.php?id=technical:about_release_candidates

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
 1.) Open a console window and enter the command below
        gksu aptitude -y install python-qt4

BASIC USAGE
    1.) Run the client program, Client.py
    2.) Run the main application, Main.pyw
    3.) Find nodes
    4.) Select a scene to render
    5.) Name the job
    6.) Submit
    7.) Render
