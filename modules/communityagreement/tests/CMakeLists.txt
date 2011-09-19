add_subdirectory( controllers )
add_subdirectory( models )

add_midas_style_test( StyleCommunityagreementNotification ${CMAKE_SOURCE_DIR}/modules/communityagreement/Notification.php )
add_midas_style_test( StyleTestsCommunityagreementControllers ${CMAKE_SOURCE_DIR}/modules/communityagreement/tests/controllers )
add_midas_style_test( StyleTestsCommunityagreementModels ${CMAKE_SOURCE_DIR}/modules/communityagreement/tests/models )