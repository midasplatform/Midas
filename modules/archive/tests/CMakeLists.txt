add_subdirectory( controllers )

# Style
add_midas_style_test( StyleArchiveControllers ${CMAKE_SOURCE_DIR}/modules/archive/controllers )
add_midas_style_test( StyleTestsArchiveControllers ${CMAKE_SOURCE_DIR}/modules/archive/tests/controllers )
add_midas_style_test( StyleArchiveNotification ${CMAKE_SOURCE_DIR}/modules/archive/Notification.php )
