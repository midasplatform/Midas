if(NOT WIN32)
  # This test is not designed to work on windows for some reason
  add_midas_test( KWUtils KWUtilsTest.php )
endif()

# Style
add_midas_style_test( StyleKWUtils ${CMAKE_SOURCE_DIR}/library/KWUtils.php )
add_midas_style_test( StyleKWUtilsTest ${CMAKE_SOURCE_DIR}/tests/library/KWUtilsTest.php )