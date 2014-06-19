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

# Create the TestConfig.php file used for testing
option(MIDAS_TEST_COVERAGE "Perform xdebug coverage. Only turn on if you need coverage." OFF)
if(MIDAS_TEST_COVERAGE)
  set(MIDAS_TEST_COVERAGE_LITERAL "true")
else()
  set(MIDAS_TEST_COVERAGE_LITERAL "false")
endif()
configure_file(${CMAKE_SOURCE_DIR}/tests/TestConfig.php.in ${CMAKE_SOURCE_DIR}/tests/TestConfig.php)

# Create the xdebug coverage directory that CTest expects
if(NOT EXISTS ${CMAKE_BINARY_DIR}/xdebugCoverage)
  file(MAKE_DIRECTORY ${CMAKE_BINARY_DIR}/xdebugCoverage)
endif()

macro(to_titlecase Arg Result)
  string(SUBSTRING ${Arg} 0 1 ArgHead)
  string(TOUPPER ${ArgHead} ArgHead)
  string(SUBSTRING ${Arg} 1 -1 ArgTail)
  set(${Result} ${ArgHead}${ArgTail})
endmacro(to_titlecase)

function(add_midas_test TestName TestFile)
  add_midas_mysql_test(${TestName} ${TestFile})
  add_midas_pgsql_test(${TestName} ${TestFile})
endfunction(add_midas_test)

function(add_midas_mysql_test TestName TestFile)
  IF(EXISTS ${CMAKE_SOURCE_DIR}/tests/configs/mysql.ini)
    add_test(
      Mysql${TestName}
      ${PHP} ${CMAKE_SOURCE_DIR}/tests/library/PHPUnit/mysqlPhpunit.php --bootstrap ${CMAKE_SOURCE_DIR}/tests/bootstrap.php ${CMAKE_CURRENT_SOURCE_DIR}/${TestFile}
    )
    set_tests_properties(
      Mysql${TestName} PROPERTIES
      FAIL_REGULAR_EXPRESSION ".*Failures: [1-9]+.*;.*Exceptions: [1-9]+.*"
      RESOURCE_LOCK MIDASDatabase
    )
  ENDIF(EXISTS ${CMAKE_SOURCE_DIR}/tests/configs/mysql.ini)  
endfunction(add_midas_mysql_test)

function(add_midas_pgsql_test TestName TestFile)
   IF(EXISTS ${CMAKE_SOURCE_DIR}/tests/configs/pgsql.ini)
    add_test(
      Pgsql${TestName}
      ${PHP} ${CMAKE_SOURCE_DIR}/tests/library/PHPUnit/pgsqlPhpunit.php --bootstrap ${CMAKE_SOURCE_DIR}/tests/bootstrap.php ${CMAKE_CURRENT_SOURCE_DIR}/${TestFile}
    )
    set_tests_properties(
      Pgsql${TestName} PROPERTIES
      FAIL_REGULAR_EXPRESSION ".*Failures: [1-9]+.*;.*Exceptions: [1-9]+.*"
      RESOURCE_LOCK MIDASDatabase
    )
  ENDIF(EXISTS ${CMAKE_SOURCE_DIR}/tests/configs/pgsql.ini)
endfunction(add_midas_pgsql_test)

function(add_midas_style_test TestName TestDir)
  if(MIDAS_RUN_STYLE_TESTS)
    add_test(
      ${TestName}
      ${PHP} ${CMAKE_SOURCE_DIR}/tests/library/PhpCheckstyle/run.php --format console --src ${TestDir}
    )
    set_tests_properties(
      ${TestName} PROPERTIES
      FAIL_REGULAR_EXPRESSION "ERROR;WARNING"
    )

    add_test(
      ${TestName}TrailingSpaces
      ${PHP} ${CMAKE_SOURCE_DIR}/tests/TrailingSpaces.php --src ${TestDir}
    )
    set_tests_properties(
      ${TestName}TrailingSpaces PROPERTIES
      FAIL_REGULAR_EXPRESSION "ERROR;WARNING"
    )
  endif()
endfunction(add_midas_style_test)

if(NOT WIN32)
  # This test is not designed to work on windows for some reason
  add_midas_test( KWUtils KWUtilsTest.php )
endif()

add_midas_style_test( StyleKWUtils ${CMAKE_SOURCE_DIR}/library/KWUtils.php )
add_midas_style_test( StyleTestsBootstrap ${CMAKE_SOURCE_DIR}/tests/bootstrap.php )
add_midas_style_test( StyleTestsControllerTestCase ${CMAKE_SOURCE_DIR}/tests/ControllerTestCase.php )
add_midas_style_test( StyleTestsDatabaseSetup ${CMAKE_SOURCE_DIR}/tests/DatabaseSetup.php )
add_midas_style_test( StyleTestsDatabaseTestCase ${CMAKE_SOURCE_DIR}/tests/DatabaseTestCase.php )
add_midas_style_test( StyleTestsKWUtilsTest ${CMAKE_SOURCE_DIR}/tests/KWUtilsTest.php )
add_midas_style_test( StyleTestsTrailingSpaces ${CMAKE_SOURCE_DIR}/tests/TrailingSpaces.php )
add_midas_style_test( StyleTestsUTF8Tools ${CMAKE_SOURCE_DIR}/tests/UTF8Tools.php )

if(NOT WIN32)
  # This test is not designed to work on Windows for some reason
  add_midas_test( KWUtils KWUtilsTest.php )
endif()

add_test(UTF8Encoding ${PHP} ${CMAKE_SOURCE_DIR}/tests/UTF8Tools.php --src ${CMAKE_SOURCE_DIR})
set_tests_properties(
  UTF8Encoding PROPERTIES
  FAIL_REGULAR_EXPRESSION "ERROR;WARNING"
  )
