add_subdirectory( controllers )
add_subdirectory( models )

add_midas_style_test( StylePackagesControllers ${CMAKE_SOURCE_DIR}/modules/packages/controllers )
add_midas_style_test( StylePackagesModels ${CMAKE_SOURCE_DIR}/modules/packages/models )
