add_subdirectory( controllers )

# Style
add_midas_style_test( StyleApiControllers ${CMAKE_SOURCE_DIR}/modules/api/controllers )
add_midas_style_test( StyleTestsApiControllers ${CMAKE_SOURCE_DIR}/modules/api/tests/controllers )
add_midas_style_test(StyleApiEnabledNotification
${CMAKE_SOURCE_DIR}/modules/api/library/APIEnabledNotification.php)
