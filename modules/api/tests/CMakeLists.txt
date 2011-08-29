#add_subdirectory( controllers )
add_subdirectory( models )

#add_midas_style_test( StyleTestsApiControllers ${CMAKE_SOURCE_DIR}/modules/api/tests/controllers )
add_midas_style_test( StyleTestsApiModels ${CMAKE_SOURCE_DIR}/modules/api/tests/models )