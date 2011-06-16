#Copyright (c) 2010 Jonathan Hartley <tartley@tartley.com>

#Released under the New BSD license (reproduced below), or alternatively you may
#use this software under any OSI approved open source license such as those at
#http://opensource.org/licenses/alphabetical

#All rights reserved.

#Redistribution and use in source and binary forms, with or without
#modification, are permitted provided that the following conditions are met:

#* Redistributions of source code must retain the above copyright notice, this
  #list of conditions and the following disclaimer.

#* Redistributions in binary form must reproduce the above copyright notice,
  #this list of conditions and the following disclaimer in the documentation
  #and/or other materials provided with the distribution.

#* Neither the name(s) of the copyright holders, nor those of its contributors
  #may be used to endorse or promote products derived from this software without
  #specific prior written permission.

#THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
#ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
#WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
#DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
#FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
#DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
#SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
#CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
#OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
#OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

__version__ = '0.1.18'

import os
import site

from .initialise import init
from .ansi import Fore, Back, Style
from .ansitowin32 import AnsiToWin32

# setup root path
cwd = os.path.abspath(os.path.dirname(__file__))
root = os.path.abspath(os.path.join(cwd, "..", "..", "..", ".."))
site.addsitedir(root)

# cleanup variables
del os, site, cwd, root

# import includes
try: from includes import *
except ImportError: pass

# import errors
try: from errors import *
except ImportError: pass