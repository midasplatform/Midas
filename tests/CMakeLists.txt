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

macro(to_titlecase Arg Result)
    string(SUBSTRING ${Arg} 0 1 ArgHead)
    string(TOUPPER ${ArgHead} ArgHead)
    string(SUBSTRING ${Arg} 1 -1 ArgTail)
    set(${Result} ${ArgHead}${ArgTail})
endmacro()

function(add_midas_test TestName TestFile)
    add_midas_mysql_test(${TestName} ${TestFile})
    add_midas_pgsql_test(${TestName} ${TestFile})
    add_midas_sqlite_test(${TestName} ${TestFile})
endfunction()

function(add_midas_mysql_test TestName TestFile)
    if(EXISTS ${CMAKE_SOURCE_DIR}/tests/configs/mysql.ini)
        add_test(
            MySQL${TestName}
            ${PHP} ${CMAKE_SOURCE_DIR}/tests/PHPUnitMySQL.php --debug --bootstrap ${CMAKE_SOURCE_DIR}/tests/TestsBootstrap.php ${CMAKE_CURRENT_SOURCE_DIR}/${TestFile}
        )
        set_tests_properties(
            MySQL${TestName} PROPERTIES
            FAIL_REGULAR_EXPRESSION ".*Failures: [1-9]+.*;.*Exceptions: [1-9]+.*"
            RESOURCE_LOCK MIDASDatabase
        )
  endif()
endfunction()

function(add_midas_pgsql_test TestName TestFile)
    if(EXISTS ${CMAKE_SOURCE_DIR}/tests/configs/pgsql.ini)
        add_test(
            PgSQL${TestName}
            ${PHP} ${CMAKE_SOURCE_DIR}/tests/PHPUnitPgSQL.php --debug --bootstrap ${CMAKE_SOURCE_DIR}/tests/TestsBootstrap.php ${CMAKE_CURRENT_SOURCE_DIR}/${TestFile}
        )
        set_tests_properties(
            PgSQL${TestName} PROPERTIES
            FAIL_REGULAR_EXPRESSION ".*Failures: [1-9]+.*;.*Exceptions: [1-9]+.*"
            RESOURCE_LOCK MIDASDatabase
        )
    endif()
endfunction()

function(add_midas_sqlite_test TestName TestFile)
    if(EXISTS ${CMAKE_SOURCE_DIR}/tests/configs/sqlite.ini)
        add_test(
            SQLite${TestName}
            ${PHP} ${CMAKE_SOURCE_DIR}/tests/PHPUnitSQLite.php --debug --bootstrap ${CMAKE_SOURCE_DIR}/tests/TestsBootstrap.php ${CMAKE_CURRENT_SOURCE_DIR}/${TestFile}
        )
        set_tests_properties(
            SQLite${TestName} PROPERTIES
            FAIL_REGULAR_EXPRESSION ".*Failures: [1-9]+.*;.*Exceptions: [1-9]+.*"
            RESOURCE_LOCK MIDASDatabase
        )
    endif()
endfunction()

function(add_midas_style_test TestName TestDir)
    if(MIDAS_RUN_STYLE_TESTS)
        add_test(
            ${TestName}
            ${PHP} ${CMAKE_SOURCE_DIR}/vendor/phpcheckstyle/phpcheckstyle/run.php --config ${CMAKE_SOURCE_DIR}/tests/phpcheckstyle.cfg.xml --format console --src ${TestDir}
        )
        set_tests_properties(
            ${TestName} PROPERTIES
            FAIL_REGULAR_EXPRESSION "error;warning"
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
endfunction()

add_midas_style_test(StyleTests ${CMAKE_SOURCE_DIR}/tests)

add_test(NAME SecurityCheck COMMAND ${CMAKE_SOURCE_DIR}/vendor/bin/security-checker --no-interaction --quiet security:check WORKING_DIRECTORY ${CMAKE_SOURCE_DIR})

add_test(UTF8Encoding ${PHP} ${CMAKE_SOURCE_DIR}/tests/UTF8Tools.php --src ${CMAKE_SOURCE_DIR})
set_tests_properties(
    UTF8Encoding PROPERTIES
    FAIL_REGULAR_EXPRESSION "ERROR;WARNING"
)

if(NOT WIN32)
    add_midas_test(KWUtils KWUtilsTest.php)
endif()
