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

from __future__ import with_statement

import os

from _ast import *
try:
    from ast import parse
except ImportError:
    parse = lambda source, filename: compile(source, filename, "exec", PyCF_ONLY_AST)

import sys
import shutil
import urllib2
import hashlib
import tempfile
from datetime import datetime
from os.path import abspath, join, dirname, isfile, isdir, basename

# -- General configuration -----------------------------------------------------

# If your documentation needs a minimal Sphinx version, state it here.
#needs_sphinx = "1.0"

# Add any Sphinx extension module names here, as strings. They can be extensions
# coming with Sphinx (named "sphinx.ext.*") or your custom ones.
extensions = [
    "sphinx.ext.autodoc",
    "sphinx.ext.doctest",
    "sphinx.ext.coverage",
    "sphinx.ext.ifconfig",
    "sphinx.ext.viewcode",
    "sphinx.ext.intersphinx"
]

pymajor, pyminor = sys.version_info[0:2]
intersphinx_mapping = {
    "python": ("http://docs.python.org/%s.%s" % (pymajor, pyminor), None),
    "sqlalchemy": ("http://www.sqlalchemy.org/docs/", None),
    "flask": ("http://flask.readthedocs.org/en/latest/", None),
    "numpy": ("http://docs.scipy.org/doc/numpy", None)
}

templates_path = ["_templates"]
source_suffix = ".rst"
master_doc = "index"

project = u"PyFarm"
root = abspath(join(dirname(__file__), "..", ".."))
sys.path.insert(0, root)
docroot = join(root, "docs", "source")
initpy = join(root, project.lower(), "__init__.py")
tmpdir = tempfile.mkdtemp(suffix="-pyfarm-docs")
assert isfile(initpy), "%s does not exist" % initpy

print "generating dynamic content"

# Parse the __init__.py file instead of importing it.  So even if we
# have code that can't be imported we can at least read the proper version
# information
print "..parsing version/author(s)"
with open(initpy, "r") as stream:
    module = parse(stream.read(), stream.name)

author = None
parsed_version = None
for obj in module.body:
    if isinstance(obj, Assign) and obj.targets[0].id == "__version__":
        parsed_version = map(lambda num: num.n, obj.value.elts)
    elif isinstance(obj, Assign) and obj.targets[0].id == "__author__":
        author = obj.value.s

assert isinstance(parsed_version, list), "did not find __version__"
assert isinstance(author, basestring), "did not find __author__"

# General information about the project.
now = datetime.now()
copyright = "%s, %s" % (now.year, author)
release = ".".join(map(str, parsed_version))
version = ".".join(map(str, parsed_version[0:2]))

# create a requirements file to
print "..python requirements"
import setup as _setup
python_requirements = join(docroot, "include", "python_requirements.rst")

with open(python_requirements, "w") as destination:
    supported_versions = ((2, 5), (2, 6), (2, 7))

    for major, minor in supported_versions:
        header = "Python %s.%s" % (major, minor)
        print >> destination, header
        print >> destination, "+" * len(header)

        for requirement in sorted(_setup.requirements(major, minor, develop=False)):
            print >> destination, "* %s" % requirement

        print >> destination

print "..TODO: jobtypes"
print "..download links"

download_release = (
    (
        "6/10/2009",
        "Stable - 0.3.216",
        "http://pyfarm.net/downloads/pyfarm_stable_v0.3.216.tar.gz",
        "f3b48856c4a4f82a1b2607b6c1b807f9",
        "Legacy Release"
    ),
)

download_other = (
    (
        "11/16/2011",
        "Client Testing - Milestone 1",
        "http://pyfarm.net/downloads/client_milestone1.tar.gz",
        "5a817b22b69cc7e5d8c8a66c723e0ea6",
        "Testing xmlrc/process protocols"
    ),
    (
        "11/25/2011",
        "Client Testing - Milestone 1.5",
        "http://pyfarm.net/downloads/client_milestone1_5.tar.gz",
        "641817a7f95d8f4fa01f8bf0ef4db3d5",
        "adding client preferences"
    ),
    (
        "12/04/2011",
        "Client Testing - Milestone 2",
        "http://pyfarm.net/downloads/client_milestone2.tar.gz",
        "54fe83c91019ac1905a1263a05a1ce82",
        "logging and remote log retrieval"
    ),
    (
        "12/11/2011",
        "Client Testing - Milestone 3",
        "http://pyfarm.net/downloads/client_milestone3.tar.gz",
        "c9bcdb17309735403142d1fc61f5e685",
        "basic job object and environment control"
    ),
    (
        "12/17/2011",
        "Client Testing - Milestone 4",
        "http://pyfarm.net/downloads/client_milestone4.tar.gz",
        "e7d5acdfbd9587f9956dc31d0ddfcb8f",
        "running job statistical information"

    ),
    (
        "1/25/2012",
        "Client Testing - Milestone 5",
        "http://pyfarm.net/downloads/client_milestone5.tar.gz",
        "a420ce6c3c1433053e3207a5644618f9",
        "updated preference objects, multicast discovery"
    )
)


download_directory = join(
    tempfile.gettempdir(),"pyfarm", "docbuild",  "downloads"
)

USE_SERVER_URL = True

if not USE_SERVER_URL and not isdir(join(docroot, "downloads")):
    os.makedirs(join(docroot, "downloads"))

if not isdir(download_directory):
    os.makedirs(download_directory)


def downloadfile(url, md5):
    destination = join(download_directory, url.split("/")[-1])

    # download the file if it does not exist
    if not isfile(destination):
        print "...downloading %s -> %s" % (url, destination)
        wwwresource = urllib2.urlopen(url)
        with open(destination, "w") as outstream:
            outstream.write(wwwresource.read())
        wwwresource.close()

    # check the hash
    filehash = hashlib.md5()
    with open(destination, "r") as sourcestream:
        while True:
            data = sourcestream.read(128)
            if not data:
                break

            filehash.update(data)

    assert filehash.hexdigest() == md5, "hash check failed for %s" % destination

    if USE_SERVER_URL:
        return destination
    else:
        local = join(basename(dirname(destination)), basename(destination))

        if not isfile(local):
            shutil.copy(destination, local)

        return local


def generatetable(stream, source_data):
    for row in source_data:
        date, name, source, md5, desc = row
        localpath = downloadfile(source, md5)
        size = os.stat(localpath).st_size / 1024

        if USE_SERVER_URL:
            data = (date, name, source, size, md5, desc)
        else:
            data = (date, name, localpath, size, md5, desc)

        print >> stream, row_template % data


downloadsrst = join(docroot, "downloads.rst")

if USE_SERVER_URL:
    row_template = '    "%s", "`%s <%s>`_", %s, %s, "%s"'
else:
    row_template = '    "%s", ":download:`%s <%s>`", %s, %s, "%s"'

table_header = '    :header: "Date", "Filename", "Size (KB)", "MD5", "Description"'
table_widths = '    :widths: 5, 10, 3, 10, 15'

with open(downloadsrst, "w") as stream:
    print >> stream, ".. this page was dynamically generated by conf.py, DO"
    print >> stream, ".. NOT MODIFY."
    print >> stream, ""
    print >> stream, "Downloads"
    print >> stream, "========="
    print >> stream, "Below are several downloads containing the source code "
    print >> stream, "for PyFarm.  In addition to releases several prototypes"
    print >> stream, "and tests are included for reference."
    print >> stream, ""
    print >> stream, ""
    print >> stream, "Releases"
    print >> stream, "--------"
    print >> stream, ".. csv-table::"
    print >> stream, '    :header: "Date", "Filename", "Size (KB)", "MD5", "Description"'
    print >> stream, table_widths
    print >> stream, ""

    # for name, source, md5, date, desc in sorted(download_release, sort_dates):
    generatetable(stream, download_release)

    print >> stream, ""
    print >> stream, "Testing/Other"
    print >> stream, "-------------"
    print >> stream, ".. csv-table::"
    print >> stream, table_header
    print >> stream, table_widths
    print >> stream, ""
    generatetable(stream, download_other)

print "generation complete"


# List of patterns, relative to source directory, that match files and
# directories to ignore when looking for source files.
exclude_patterns = ["include/*", "downloads/*"]
pygments_style = "sphinx"
html_theme = "default"
html_static_path = ["_static"]
htmlhelp_basename = "PyFarmdoc"
latex_elements = {}

# Grouping the document tree into LaTeX files. List of tuples
# (source start file, target name, title, author, documentclass [howto/manual]).
latex_documents = [
  ("index", "PyFarm.tex", u"PyFarm Documentation",
   u"Oliver Palmer", "manual"),
]


man_pages = [
    ("index", "pyfarm", u"PyFarm Documentation",
     [u"Oliver Palmer"], 1)
]

texinfo_documents = [
  ("index", "PyFarm", u"PyFarm Documentation",
   u"Oliver Palmer", "PyFarm", "A Python based distributed job system",
   "Miscellaneous"),
]