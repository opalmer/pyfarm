# mod_python dispatcher - Chapter 19 - dispatcher.py

from mod_python import apache
import re

def raise404(logmsg):
    """Log an explanatory error message and send 404 to the client"""
    apache.log_error(logmsg, apache.APLOG_ERR)
    raise apache.SERVER_RETURN, apache.HTTP_NOT_FOUND

def gethandlerfunc(modname):
    """Given a module name from a URL, obtain the handler function from it
    and return the function object."""
    try:
        # Import the module
        mod = __import__(modname)
    except ImportError:
        # No module with this name
        raise404("Couldn't import module " + modname)

    try:
        # Find the handler function
        handler = mod.handler
    except AttributeError:
        # No handler function
        raise404("Couldn't find handler function in module " + modname)

    if not callable(handler):
        # It's not a function
        raise404("Handler is not callable in module " + modname)

    return handler

def gethandlername(URL):
    """Given a URL, find the handler module name"""
    match = re.search("/([a-zA-Z0-9_-]+)\.prog($|/|\?)", URL)
    if not match:
        # Couldn't find the requested module
        raise404("Couldn't find a module name in URL " + URL)
    return match.group(1)

def handler(req):
    """Main entry point to the program.  Find the handler function,
    call it, and return the result."""
    name = gethandlername(req.uri)
    if name == "dispatcher":
        raise404("Can't display the dispatcher")
    handlerfunc = gethandlerfunc(name)
    return handlerfunc(req)
