add_subdirectory( controllers )
add_subdirectory( models )

add_midas_style_test( StyleValidationControllers ${CMAKE_SOURCE_DIR}/modules/validation/controllers )
add_midas_style_test( StyleValidationModels ${CMAKE_SOURCE_DIR}/modules/validation/models )
add_midas_style_test( StyleTestsValidationControllers ${CMAKE_SOURCE_DIR}/modules/validation/tests/controllers )
add_midas_style_test( StyleTestsValidationModels ${CMAKE_SOURCE_DIR}/modules/validation/tests/models )
