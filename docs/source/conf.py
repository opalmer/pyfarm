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
    parse = lambda source, filename: compile(source, filename, 'exec', PyCF_ONLY_AST)

import sys
import shutil
import urllib2
import hashlib
import tempfile
from datetime import datetime
from os.path import abspath, join, dirname, isfile, isdir, basename

# -- General configuration -----------------------------------------------------

# If your documentation needs a minimal Sphinx version, state it here.
#needs_sphinx = '1.0'

# Add any Sphinx extension module names here, as strings. They can be extensions
# coming with Sphinx (named 'sphinx.ext.*') or your custom ones.
extensions = [
    'sphinx.ext.autodoc',
    'sphinx.ext.doctest',
    'sphinx.ext.coverage',
    'sphinx.ext.ifconfig',
    'sphinx.ext.viewcode',
    'sphinx.ext.intersphinx'
]

pymajor, pyminor = sys.version_info[0:2]
intersphinx_mapping = {
    'python': ('http://docs.python.org/%s.%s' % (pymajor, pyminor), None),
    'sqlalchemy': ('http://www.sqlalchemy.org/docs/', None)
}

# Add any paths that contain templates here, relative to this directory.
templates_path = ['_templates']

# The suffix of source filenames.
source_suffix = '.rst'

# The encoding of source files.
#source_encoding = 'utf-8-sig'

# The master toctree document.
master_doc = 'index'

project = u'PyFarm'
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

# The language for content autogenerated by Sphinx. Refer to documentation
# for a list of supported languages.
#language = None

# There are two options for replacing |today|: either, you set today to some
# non-false value, then it is used:
#today = ''
# Else, today_fmt is used as the format for a strftime call.
#today_fmt = '%B %d, %Y'

# List of patterns, relative to source directory, that match files and
# directories to ignore when looking for source files.
exclude_patterns = ["include/*", "downloads/*"]

# The reST default role (used for this markup: `text`) to use for all documents.
#default_role = None

# If true, '()' will be appended to :func: etc. cross-reference text.
#add_function_parentheses = True

# If true, the current module name will be prepended to all description
# unit titles (such as .. function::).
#add_module_names = True

# If true, sectionauthor and moduleauthor directives will be shown in the
# output. They are ignored by default.
#show_authors = False

# The name of the Pygments (syntax highlighting) style to use.
pygments_style = 'sphinx'

# A list of ignored prefixes for module index sorting.
#modindex_common_prefix = []


# -- Options for HTML output ---------------------------------------------------

# The theme to use for HTML and HTML Help pages.  See the documentation for
# a list of builtin themes.
html_theme = 'default'

# Theme options are theme-specific and customize the look and feel of a theme
# further.  For a list of options available for each theme, see the
# documentation.
#html_theme_options = {}

# Add any paths that contain custom themes here, relative to this directory.
#html_theme_path = []

# The name for this set of Sphinx documents.  If None, it defaults to
# "<project> v<release> documentation".
#html_title = None

# A shorter title for the navigation bar.  Default is the same as html_title.
#html_short_title = None

# The name of an image file (relative to this directory) to place at the top
# of the sidebar.
#html_logo = None

# The name of an image file (within the static path) to use as favicon of the
# docs.  This file should be a Windows icon file (.ico) being 16x16 or 32x32
# pixels large.
#html_favicon = None

# Add any paths that contain custom static files (such as style sheets) here,
# relative to this directory. They are copied after the builtin static files,
# so a file named "default.css" will overwrite the builtin "default.css".
html_static_path = ['_static']

# If not '', a 'Last updated on:' timestamp is inserted at every page bottom,
# using the given strftime format.
#html_last_updated_fmt = '%b %d, %Y'

# If true, SmartyPants will be used to convert quotes and dashes to
# typographically correct entities.
#html_use_smartypants = True

# Custom sidebar templates, maps document names to template names.
#html_sidebars = {}

# Additional templates that should be rendered to pages, maps page names to
# template names.
#html_additional_pages = {}

# If false, no module index is generated.
#html_domain_indices = True

# If false, no index is generated.
#html_use_index = True

# If true, the index is split into individual pages for each letter.
#html_split_index = False

# If true, links to the reST sources are added to the pages.
#html_show_sourcelink = True

# If true, "Created using Sphinx" is shown in the HTML footer. Default is True.
#html_show_sphinx = True

# If true, "(C) Copyright ..." is shown in the HTML footer. Default is True.
#html_show_copyright = True

# If true, an OpenSearch description file will be output, and all pages will
# contain a <link> tag referring to it.  The value of this option must be the
# base URL from which the finished HTML is served.
#html_use_opensearch = ''

# This is the file name suffix for HTML files (e.g. ".xhtml").
#html_file_suffix = None

# Output file base name for HTML help builder.
htmlhelp_basename = 'PyFarmdoc'


# -- Options for LaTeX output --------------------------------------------------

latex_elements = {
# The paper size ('letterpaper' or 'a4paper').
#'papersize': 'letterpaper',

# The font size ('10pt', '11pt' or '12pt').
#'pointsize': '10pt',

# Additional stuff for the LaTeX preamble.
#'preamble': '',
}

# Grouping the document tree into LaTeX files. List of tuples
# (source start file, target name, title, author, documentclass [howto/manual]).
latex_documents = [
  ('index', 'PyFarm.tex', u'PyFarm Documentation',
   u'Oliver Palmer', 'manual'),
]

# The name of an image file (relative to this directory) to place at the top of
# the title page.
#latex_logo = None

# For "manual" documents, if this is true, then toplevel headings are parts,
# not chapters.
#latex_use_parts = False

# If true, show page references after internal links.
#latex_show_pagerefs = False

# If true, show URL addresses after external links.
#latex_show_urls = False

# Documents to append as an appendix to all manuals.
#latex_appendices = []

# If false, no module index is generated.
#latex_domain_indices = True


# -- Options for manual page output --------------------------------------------

# One entry per manual page. List of tuples
# (source start file, name, description, authors, manual section).
man_pages = [
    ('index', 'pyfarm', u'PyFarm Documentation',
     [u'Oliver Palmer'], 1)
]

# If true, show URL addresses after external links.
#man_show_urls = False


# -- Options for Texinfo output ------------------------------------------------

# Grouping the document tree into Texinfo files. List of tuples
# (source start file, target name, title, author,
#  dir menu entry, description, category)
texinfo_documents = [
  ('index', 'PyFarm', u'PyFarm Documentation',
   u'Oliver Palmer', 'PyFarm', 'One line description of project.',
   'Miscellaneous'),
]

# Documents to append as an appendix to all manuals.
#texinfo_appendices = []

# If false, no module index is generated.
#texinfo_domain_indices = True

# How to display URL addresses: 'footnote', 'no', or 'inline'.
#texinfo_show_urls = 'footnote'

