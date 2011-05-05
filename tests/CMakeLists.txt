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

add_midas_style_test( StyleCoreAppController ${CMAKE_SOURCE_DIR}/core/AppController.php )
add_midas_style_test( StyleCoreAppComponent ${CMAKE_SOURCE_DIR}/core/AppComponent.php )
add_midas_style_test( StyleCoreAppFilters ${CMAKE_SOURCE_DIR}/core/AppFilters.php )
add_midas_style_test( StyleCoreAppForm ${CMAKE_SOURCE_DIR}/core/AppForm.php )
add_midas_style_test( StyleCoreBootstrap ${CMAKE_SOURCE_DIR}/core/Bootstrap.php )
add_midas_style_test( StyleCoreControllers ${CMAKE_SOURCE_DIR}/core/controllers )
add_midas_style_test( StyleCoreModels ${CMAKE_SOURCE_DIR}/core/models )
add_midas_style_test( StyleLibraryMidas ${CMAKE_SOURCE_DIR}/library/MIDAS )

add_subdirectory(core)
