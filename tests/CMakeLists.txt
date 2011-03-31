# function to add a new PHP based coverage test to CDash
#
function(add_midas_test TestName TestFile)
  add_test(
    ${TestName}
    ${PHP_UNIT} --bootstrap ${CMAKE_SOURCE_DIR}/tests/bootstrap.php ${CMAKE_CURRENT_SOURCE_DIR}/${TestFile}
  )
  set_tests_properties(
    ${TestName} PROPERTIES
    FAIL_REGULAR_EXPRESSION ".*Failures: [1-9]+.*;.*Exceptions: [1-9]+.*"
  )
endfunction(add_midas_test)

add_subdirectory(core)
