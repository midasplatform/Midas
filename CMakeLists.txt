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

project( Midas )

#-----------------------------------------------------------------------------
cmake_minimum_required( VERSION 2.8 )

#-----------------------------------------------------------------------------
#  Find the php executable
find_program(PHP "php" CACHE STRING "PHP executable.")
if(NOT PHP)
 message(FATAL_ERROR "Please set the PHP executable")
endif()

#-----------------------------------------------------------------------------
#  Drop all tables from the testing DB
#  Then re-install the DB, set a Default assetstore, and install all modules
message(STATUS "Setting up database for module testing, please wait...")
execute_process(
  COMMAND ${PHP} ${CMAKE_CURRENT_SOURCE_DIR}/tests/DatabaseSetup.php
  RESULT_VARIABLE databaseSetup_RESULT
  OUTPUT_VARIABLE databaseSetup_OUT
  ERROR_VARIABLE databaseSetup_ERR)

if(NOT databaseSetup_RESULT EQUAL 0)
  message(STATUS "DatabaseSetup output: ${databaseSetup_OUT}")
  message(FATAL_ERROR "DatabaseSetup error: ${databaseSetup_ERR}")
endif()

message(STATUS "Database setup is complete")

#----------------------------------------------------------------------------
# Setup testing and required parameters for testing

include(CTest)
option( MIDAS_RUN_STYLE_TESTS "Should MIDAS run PHP style checking tests?" ON )
set( SERVER_BIN_DIR ${CMAKE_CURRENT_BINARY_DIR} )
add_subdirectory(tests)
add_subdirectory(core/tests)
add_subdirectory(modules)
