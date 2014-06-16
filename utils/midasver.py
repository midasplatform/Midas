#! /usr/bin/env python
# -*- coding: utf-8 -*-
#=============================================================================
# MIDAS Server
# Copyright (c) Kitware SAS. 26 rue Louis Guérin. 69100 Villeurbanne, FRANCE
# All rights reserved.
# More information http://www.kitware.com
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#         http://www.apache.org/licenses/LICENSE-2.0.txt
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
#=============================================================================

"""
Simple script for versioning the documentation stored on the midaswiki.
Essentially, this is only useful as an example.
"""
import getpass
import mwdoc
import sys

if __name__ == '__main__':
    if len(sys.argv) != 3:
        print "Please enter a source and destination version"
        sys.exit(-1)
    sourceVersion = sys.argv[1]
    destinationVersion = sys.argv[2]
    doc = mwdoc.Documentation('www.kitware.com', '/midaswiki/')
    print "This application will version the Midas Wiki documentation from ",
    print "%s to %s." % (sourceVersion, destinationVersion)
    username = raw_input("Username: ")
    password = getpass.getpass("Password: ")
    doc.login(username, password)
    prefixes = ['Documentation','Template:Documentation']
    doc.versionPages(sourceVersion, destinationVersion, prefixes)
