add_subdirectory( controllers )

add_midas_style_test( StyleVisualizeNotification ${CMAKE_SOURCE_DIR}/modules/visualize/Notification.php )
add_midas_style_test( StyleTestsVisualizeControllers ${CMAKE_SOURCE_DIR}/modules/visualize/tests/controllers )
