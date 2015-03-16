#=============================================================================
# Midas Server
# Copyright Kitware SAS, 26 rue Louis Guérin, 69100 Villeurbanne, France.
# All rights reserved.
# For more information visit http://www.kitware.com/.
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

# This script should be used to create a new module in modules directory.
# Call as
#     cmake -P NewModule.cmake modulename
# Where modulename is the name of your module. The name should contain no spaces.
#
cmake_minimum_required(VERSION 2.8.7)
if(NOT DEFINED CMAKE_ARGV3)
    message(FATAL_ERROR "Must pass in module name as an argument: cmake -P NewModule.cmake mymodule")
endif()

find_file(COMPOSER_LOCK "composer.lock" PATHS ${CMAKE_CURRENT_LIST_DIR}/../ NO_DEFAULT_PATH DOC "Path to the composer lock file")
if(NOT COMPOSER_LOCK)
    message(FATAL_ERROR "Must run composer install before creating a module")
endif()

set(MUUID "00000000-0000-4000-a000-000000000000")
find_program(PHP "php" DOC "Path to php")
if(PHP)
    find_program(UUID "uuid" PATHS ${CMAKE_CURRENT_LIST_DIR}/../vendor/bin NO_DEFAULT_PATH DOC "Path to uuid")
    if(UUID)
        execute_process(COMMAND ${PHP} ${UUID} generate 4 OUTPUT_VARIABLE MUUID_OUTPUT)
        string(STRIP ${MUUID_OUTPUT} MUUID)
    endif()
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
