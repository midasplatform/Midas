add_subdirectory( controllers )
add_subdirectory( models )

add_midas_mysql_test( RemoteprocessingApi ApiTest.php )

add_midas_style_test( StyleTestsRemoteprocessingControllers ${CMAKE_SOURCE_DIR}/modules/remoteprocessing/tests/controllers )
add_midas_style_test( StyleTestsRemoteprocessingModels ${CMAKE_SOURCE_DIR}/modules/remoteprocessing/tests/models )