add_subdirectory( controllers )

add_midas_style_test( StyleReadmesNotification ${CMAKE_SOURCE_DIR}/modules/communityagreement/Notification.php )
add_midas_style_test( StyleTestsReadmesControllers ${CMAKE_SOURCE_DIR}/modules/communityagreement/tests/controllers )
