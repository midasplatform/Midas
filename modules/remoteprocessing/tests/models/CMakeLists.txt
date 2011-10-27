add_subdirectory( base )

#Style
add_midas_style_test( StyleRemoteprocessingModelsBase ${CMAKE_SOURCE_DIR}/modules/remoteprocessing/models/base/ )
add_midas_style_test( StyleRemoteprocessingModelsPdo ${CMAKE_SOURCE_DIR}/modules/remoteprocessing/models/pdo/ )
add_midas_style_test( StyleRemoteprocessingModelsDao ${CMAKE_SOURCE_DIR}/modules/remoteprocessing/models/dao/ )
