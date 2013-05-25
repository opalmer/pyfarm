# No shebang line, this module is meant to be imported
#
# Copyright 2013 Oliver Palmer
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#    http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.

import os
import imp
import inspect

from pyfarm import errors
from pyfarm.logger import Logger
from pyfarm.pref.jobtypes import JobTypePreferences

jobtype_prefs = JobTypePreferences()

PATHS = []
JOBTYPES = {}

__all__ = ['find', 'jobtype', 'jobtypes', 'paths']

logger = Logger(__name__)

def paths():
    """
    returns the paths we should be searching in when looking for
    jobtype modules
    """
    # should only run the search once
    if PATHS:
        return PATHS

    logger.debug(
        "searching for jobtype paths as defined in preferences and $PYFARM_JOBTYPES"
    )

    def addpath(path):
        if path in PATHS: return
        elif not path: return
        elif not os.path.isdir(path):
            logger.warning(
                "%s is not a directory, skipping for jobtype search" % path
            )
        else:
            PATHS.append(path)
    # end addpath

    # iterate over paths both preferences and the PYFARM_JOBTYPES
    # environment variable and run each result through addpath
    map(addpath, jobtype_prefs.get('search-paths'))
    map(addpath, os.environ.get('PYFARM_JOBTYPES', '').split(os.pathsep))

    return PATHS
# end paths

def find(name=None):
    """
    Depending on the value of name either find the requested name or all
    results.
    """
    if name is not None and name in JOBTYPES:
        logger.debug("jobtype %s is already loaded" % name)
        return JOBTYPES[name]

    jobtype_path = None
    search_paths = paths()
    jobtypes = {}

    if name is not None:
        logger.debug("searching for jobtype %s in %s" % (name, search_paths))

    for path in search_paths:
        for filename in os.listdir(path):
            # skip special files
            if filename.startswith("__") or filename.startswith("."):
                continue

            jobtype_path = os.path.join(path, filename)
            if os.path.isfile(jobtype_path):
                fname, fextension = os.path.splitext(filename)

                if name is not None:
                    if fname == name and fextension == ".py":
                        break
                    else:
                        jobtype_path = None
                else:
                    if fname not in jobtypes:
                        jobtypes[fname] = []

                    if fextension == ".py":
                        jobtypes[fname].append(jobtype_path)

    if name is None:
        return jobtypes

    if jobtype_path is None:
        raise errors.JobTypeNotFoundError(jobtype=name, paths=search_paths)

    return jobtype_path
# end find

def jobtype(name):
    if name in JOBTYPES:
        return JOBTYPES[name]

    jobtype_path = find(name)
    logger.debug("preparing to load jobtype: %s" % jobtype_path)

    # load the module itself but dont' catch any exceptions that may occur
    try:
        paths = [os.path.dirname(jobtype_path)]
        stream, path, description = imp.find_module(name, paths)
        module = imp.load_module(name, stream, path, description)

    finally:
        stream.close()

    # make sure
    if not hasattr(module, 'Job'):
        raise AttributeError("%s does not contain a Job class" % stream.name)

    elif not inspect.isclass(module.Job):
        raise TypeError("%s must be a class" % module.Job)

    elif not issubclass(module.Job, Job):
        raise TypeError("%s class must subclass %s" % (module.Job, Job))

    return module.Job
# end jobtype

def jobtypes():
    """returns a list of jobtype names from all locations"""
    return find().keys()
# end jobtypes
