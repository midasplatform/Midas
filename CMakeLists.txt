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

cmake_minimum_required(VERSION 2.8)
project(Midas)

find_program(PHP "php" CACHE STRING "PHP executable")
if(NOT PHP)
    message(FATAL_ERROR "Please set the path to the PHP executable")
endif()

find_file(COMPOSER_LOCK "composer.lock" PATHS ${CMAKE_CURRENT_SOURCE_DIR} NO_DEFAULT_PATH)
if(NOT COMPOSER_LOCK)
    find_file(COMPOSER_PHAR "composer.phar")
    if(NOT COMPOSER_PHAR)
        message(FATAL_ERROR "Please set the path to composer.phar")
    endif()
    execute_process(
        COMMAND ${PHP} ${COMPOSER_PHAR} install
        RESULT_VARIABLE COMPOSER_INSTALL_RESULT
        OUTPUT_VARIABLE COMPOSER_INSTALL_OUTPUT
        ERROR_VARIABLE COMPOSER_INSTALL_ERROR)
    if(NOT COMPOSER_INSTALL_RESULT EQUAL 0)
        message(STATUS "Composer install output: ${COMPOSER_INSTALL_OUTPUT}")
        message(FATAL_ERROR "Composer install error: ${COMPOSER_INSTALL_ERROR}")
    endif()
    message(STATUS "Composer install is complete")
endif()

#-----------------------------------------------------------------------------
# Drop all tables from the testing database, then reinstall the database, set
# a default asset store, and install all modules
#-----------------------------------------------------------------------------

message(STATUS "Setting up database(s) for testing, please wait...")
execute_process(
    COMMAND ${PHP} ${CMAKE_CURRENT_SOURCE_DIR}/tests/DatabaseSetup.php
    RESULT_VARIABLE DATABASE_SETUP_RESULT
    OUTPUT_VARIABLE DATABASE_SETUP_OUTPUT
    ERROR_VARIABLE DATABASE_SETUP_ERROR)
if(NOT DATABASE_SETUP_RESULT EQUAL 0)
    message(STATUS "Database setup output: ${DATABASE_SETUP_OUTPUT}")
    message(FATAL_ERROR "Database setup error: ${DATABASE_SETUP_ERROR}")
endif()
message(STATUS "Database setup is complete")

#-----------------------------------------------------------------------------
# Setup testing and required parameters for testing
#-----------------------------------------------------------------------------

include(CTest)
option(MIDAS_RUN_STYLE_TESTS "Run PHP style tests?" ON)
set(SERVER_BIN_DIR ${CMAKE_CURRENT_BINARY_DIR})

add_subdirectory(tests)
add_subdirectory(core/tests)
add_subdirectory(modules)
