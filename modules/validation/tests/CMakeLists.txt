add_subdirectory( controllers )
add_subdirectory( models )

add_midas_style_test( StyleTestsHelloworldControllers ${CMAKE_SOURCE_DIR}/modules/helloworld/tests/controllers )
add_midas_style_test( StyleTestsHelloworldModels ${CMAKE_SOURCE_DIR}/modules/helloworld/tests/models )