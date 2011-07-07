# function to add a new PHP based coverage test to CDash
#
function(add_midas_test TestName TestFile)
  IF(EXISTS ${CMAKE_SOURCE_DIR}/tests/configs/mysql.ini)
    add_test(
      Mysql${TestName}
      ${PHP} ${CMAKE_SOURCE_DIR}/tests/library/PHPUnit/mysqlPhpunit.php --bootstrap ${CMAKE_SOURCE_DIR}/tests/bootstrap.php ${CMAKE_CURRENT_SOURCE_DIR}/${TestFile}
    )
    set_tests_properties(
      Mysql${TestName} PROPERTIES
      FAIL_REGULAR_EXPRESSION ".*Failures: [1-9]+.*;.*Exceptions: [1-9]+.*"
    )
  ENDIF(EXISTS ${CMAKE_SOURCE_DIR}/tests/configs/mysql.ini)

  IF(EXISTS ${CMAKE_SOURCE_DIR}/tests/configs/pgsql.ini)
    add_test(
      Pgsql${TestName}
      ${PHP} ${CMAKE_SOURCE_DIR}/tests/library/PHPUnit/pgsqlPhpunit.php --bootstrap ${CMAKE_SOURCE_DIR}/tests/bootstrap.php ${CMAKE_CURRENT_SOURCE_DIR}/${TestFile}
    )
    set_tests_properties(
      Pgsql${TestName} PROPERTIES
      FAIL_REGULAR_EXPRESSION ".*Failures: [1-9]+.*;.*Exceptions: [1-9]+.*"
    )
  ENDIF(EXISTS ${CMAKE_SOURCE_DIR}/tests/configs/pgsql.ini)
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
    )
  ENDIF(EXISTS ${CMAKE_SOURCE_DIR}/tests/configs/pgsql.ini)
endfunction(add_midas_pgsql_test)

function(add_midas_style_test TestName TestDir)
  add_test(
    ${TestName}
    ${PHP} ${CMAKE_SOURCE_DIR}/tests/library/PhpCheckstyle/run.php --format console --src ${TestDir}
  )
  set_tests_properties(
    ${TestName} PROPERTIES
    FAIL_REGULAR_EXPRESSION "ERROR;WARNING"
  )
endfunction(add_midas_style_test)

add_midas_style_test( StyleTestsControllerTestCase ${CMAKE_SOURCE_DIR}/tests/ControllerTestCase.php )
add_midas_style_test( StyleTestsDatabaseTestCase ${CMAKE_SOURCE_DIR}/tests/DatabaseTestCase.php )

