add_subdirectory( base )

#Style
add_midas_style_test( StyleHelloworldModelsBase ${CMAKE_SOURCE_DIR}/modules/helloworld/models/base/ )
add_midas_style_test( StyleHelloworldModelsPdo ${CMAKE_SOURCE_DIR}/modules/helloworld/models/pdo/ )
add_midas_style_test( StyleHelloworldModelsDao ${CMAKE_SOURCE_DIR}/modules/helloworld/models/dao/ )
