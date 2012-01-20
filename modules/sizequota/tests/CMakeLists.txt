add_subdirectory( controllers )
add_subdirectory( models )

# Style
add_midas_style_test( StyleSizequotaControllers ${CMAKE_SOURCE_DIR}/modules/sizequota/controllers )
add_midas_style_test( StyleSizequotaModels ${CMAKE_SOURCE_DIR}/modules/sizequota/models )
add_midas_style_test( StyleSizequotaNotification ${CMAKE_SOURCE_DIR}/modules/sizequota/Notification.php )
add_midas_style_test( StyleTestsSizequotaControllers ${CMAKE_SOURCE_DIR}/modules/sizequota/tests/controllers )
add_midas_style_test( StyleTestsSizequotaModels ${CMAKE_SOURCE_DIR}/modules/sizequota/tests/models )
