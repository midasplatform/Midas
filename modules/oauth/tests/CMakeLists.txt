add_subdirectory( controllers )

add_midas_style_test( StyleOauthControllers ${CMAKE_SOURCE_DIR}/modules/oauth/controllers )
add_midas_style_test( StyleOauthModels ${CMAKE_SOURCE_DIR}/modules/oauth/models )
add_midas_style_test( StyleOauthNotification ${CMAKE_SOURCE_DIR}/modules/oauth/Notification.php )
