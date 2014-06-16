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

# This CMake script should be used to create a new Midas module. Requires CMake v2.8.3+
# Call as
#   cmake -P NewModule.cmake modulename
# Where modulename is the name of your module. It should have no spaces in it.
#
if(NOT DEFINED CMAKE_ARGV3)
  message(FATAL_ERROR "Must pass in module name as an argument: cmake -P NewModule.cmake mymodule")
endif()

set(moduleName "${CMAKE_ARGV3}")
set(templateRoot "${CMAKE_CURRENT_LIST_DIR}/moduleTemplate")
string(SUBSTRING "${moduleName}" 0 1  firstChar)
string(SUBSTRING "${moduleName}" 1 -1 restOfString)
string(TOUPPER ${firstChar} firstChar)
string(TOLOWER "${restOfString}" restOfString)
string(TOLOWER "${moduleName}" MN)
set(MN_CAP "${firstChar}${restOfString}")
set(moduleRoot "${CMAKE_CURRENT_LIST_DIR}/../modules/${MN}")

message("Creating skeleton module at ${moduleRoot}")
if(EXISTS "${moduleRoot}")
  message(FATAL_ERROR "File or directory already exists: ${moduleRoot}")
endif()

file(MAKE_DIRECTORY "${moduleRoot}")
file(GLOB_RECURSE templateFiles RELATIVE "${templateRoot}" "${templateRoot}/*")
foreach(templateFile ${templateFiles})
  set(fullPath "${templateRoot}/${templateFile}")
  if(IS_DIRECTORY "${fullPath}")
    file(MAKE_DIRECTORY "${moduleRoot}/${templateFile}")
  else()
    configure_file("${fullPath}" "${moduleRoot}/${templateFile}" @ONLY)
  endif()
endforeach()
message("Done")
