add_subdirectory( controllers )

add_midas_style_test( StyleCleanupControllers ${CMAKE_SOURCE_DIR}/modules/cleanup/controllers )
add_midas_style_test( StyleCleanupModels ${CMAKE_SOURCE_DIR}/modules/cleanup/models )
add_midas_style_test( StyleCleanupNotification ${CMAKE_SOURCE_DIR}/modules/cleanup/Notification.php )
