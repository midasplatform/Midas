add_subdirectory( controllers )

add_midas_style_test( StyleKeyfilesControllers ${CMAKE_SOURCE_DIR}/modules/keyfiles/controllers )
add_midas_style_test( StyleKeyfilesModels ${CMAKE_SOURCE_DIR}/modules/keyfiles/models )
add_midas_style_test( StyleKeyfilesNotification ${CMAKE_SOURCE_DIR}/modules/keyfiles/Notification.php )
