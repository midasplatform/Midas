#add_midas_mysql_test( RemoteprocessingJobController JobControllerTest.php )
#add_midas_mysql_test( RemoteprocessingExecutableController ExecutableControllerTest.php )
add_midas_mysql_test( RemoteprocessingConfigController ConfigControllerTest.php )

# Syle
add_midas_style_test( StyleRemoteprocessingControllers ${CMAKE_SOURCE_DIR}/modules/remoteprocessing/controllers )