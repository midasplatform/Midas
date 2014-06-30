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
This script simply takes a list of issues that have been dumped from Mantis
via CSV and formats them into a wiki table. It takes the three headers of the
CSV and makes them the three headers of the wiki table. I use this to create
a changelog for the Midas Wiki.
"""
import sys
import csv

if __name__ == '__main__':
    reader = csv.reader(open(sys.argv[1], 'rb'), delimiter=',')
    writer = open(sys.argv[2],'wb')
    count = 0
    for row in reader:
        if count == 0:
            newRow = "{| border=1\n!%s\n!%s\n!%s\n" % (row[0], row[1], row[2])
        else:
            newRow = "|----\n|[http://public.kitware.com/MidasBT/view.php?id=%s %s]\n|%s\n|%s\n" % (row[0], row[0], row[1], row[2])
        writer.write(newRow)
        count +=1
    writer.write("|----\n|}\n")
    writer.close()
