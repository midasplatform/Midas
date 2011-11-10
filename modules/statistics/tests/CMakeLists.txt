add_subdirectory( controllers )
add_subdirectory( models )

# Style
add_midas_style_test( StyleStatisticsControllers ${CMAKE_SOURCE_DIR}/modules/statistics/controllers )
add_midas_style_test( StyleStatisticsModels ${CMAKE_SOURCE_DIR}/modules/statistics/models )
add_midas_style_test( StyleStatisticsNotification ${CMAKE_SOURCE_DIR}/modules/statistics/Notification.php )
