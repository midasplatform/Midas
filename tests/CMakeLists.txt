# Create the configuredVars.php file used for testing
option(MIDAS_TEST_COVERAGE "Perform xdebug coverage. Only turn on if you need coverage." OFF)

file(WRITE ${CMAKE_SOURCE_DIR}/tests/configuredVars.php
     "<?php
     define('CMAKE_BINARY_DIR', '${CMAKE_BINARY_DIR}');
     ")
file(APPEND ${CMAKE_SOURCE_DIR}/tests/configuredVars.php "define('MIDAS_TEST_COVERAGE', ")
if(MIDAS_TEST_COVERAGE)
  file(APPEND ${CMAKE_SOURCE_DIR}/tests/configuredVars.php "true")
else()
  file(APPEND ${CMAKE_SOURCE_DIR}/tests/configuredVars.php "false")
endif()
file(APPEND ${CMAKE_SOURCE_DIR}/tests/configuredVars.php "); ?>")

# Create the xdebug coverage directory that CTest expects
if(NOT EXISTS ${CMAKE_BINARY_DIR}/xdebugCoverage)
  file(MAKE_DIRECTORY ${CMAKE_BINARY_DIR}/xdebugCoverage)
endif()

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
      ${PHP} ${CMAKE_SOURCE_DIR}/tests/library/MIDASStyleChecker.php --src ${TestDir}
    )
    set_tests_properties(
      ${TestName}TrailingSpaces PROPERTIES
      FAIL_REGULAR_EXPRESSION "ERROR;WARNING"
    )
  endif()
endfunction(add_midas_style_test)

add_midas_style_test( StyleTestsControllerTestCase ${CMAKE_SOURCE_DIR}/tests/ControllerTestCase.php )
add_midas_style_test( StyleTestsDatabaseTestCase ${CMAKE_SOURCE_DIR}/tests/DatabaseTestCase.php )

add_subdirectory( library )
