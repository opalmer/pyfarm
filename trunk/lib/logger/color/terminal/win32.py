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

# from winbase.h
STDOUT = -11
STDERR = -12

try:
    from ctypes import windll
except ImportError:
    windll = None
    SetConsoleTextAttribute = lambda *_: None
else:
    from ctypes import (
        byref, Structure, c_char, c_short, c_uint32, c_ushort
    )

    handles = {
        STDOUT: windll.kernel32.GetStdHandle(STDOUT),
        STDERR: windll.kernel32.GetStdHandle(STDERR),
    }

    SHORT = c_short
    WORD = c_ushort
    DWORD = c_uint32
    TCHAR = c_char

    class COORD(Structure):
        """struct in wincon.h"""
        _fields_ = [
            ('X', SHORT),
            ('Y', SHORT),
        ]

    class  SMALL_RECT(Structure):
        """struct in wincon.h."""
        _fields_ = [
            ("Left", SHORT),
            ("Top", SHORT),
            ("Right", SHORT),
            ("Bottom", SHORT),
        ]

    class CONSOLE_SCREEN_BUFFER_INFO(Structure):
        """struct in wincon.h."""
        _fields_ = [
            ("dwSize", COORD),
            ("dwCursorPosition", COORD),
            ("wAttributes", WORD),
            ("srWindow", SMALL_RECT),
            ("dwMaximumWindowSize", COORD),
        ]

    def GetConsoleScreenBufferInfo(stream_id):
        handle = handles[stream_id]
        csbi = CONSOLE_SCREEN_BUFFER_INFO()
        success = windll.kernel32.GetConsoleScreenBufferInfo(
            handle, byref(csbi))
        # This fails when imported via setup.py when installing using 'pip'
        # presumably the fix is that running setup.py should not trigger all
        # this activity.
        # assert success
        return csbi

    def SetConsoleTextAttribute(stream_id, attrs):
        handle = handles[stream_id]
        success = windll.kernel32.SetConsoleTextAttribute(handle, attrs)
        assert success

    def SetConsoleCursorPosition(stream_id, position):
        handle = handles[stream_id]
        position = COORD(*position)
        success = windll.kernel32.SetConsoleCursorPosition(handle, position)
        assert success

    def FillConsoleOutputCharacter(stream_id, char, length, start):
        handle = handles[stream_id]
        char = TCHAR(char)
        length = DWORD(length)
        start = COORD(*start)
        num_written = DWORD(0)
        # AttributeError: function 'FillConsoleOutputCharacter' not found
        # could it just be that my types are wrong?
        success = windll.kernel32.FillConsoleOutputCharacter(
            handle, char, length, start, byref(num_written))
        assert success
        return num_written.value


if __name__=='__main__':
    x = GetConsoleScreenBufferInfo(STDOUT)
    print(x.dwSize)
    print(x.dwCursorPosition)
    print(x.wAttributes)
    print(x.srWindow)
    print(x.dwMaximumWindowSize)

