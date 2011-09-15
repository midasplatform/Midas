add_subdirectory( components )

add_midas_test( BatchmakeConfigController ConfigControllerTest.php )

# Style
add_midas_style_test( StyleBatchmakeControllers ${CMAKE_SOURCE_DIR}/modules/batchmake/controllers )
add_midas_style_test( StyleTestsBatchmakeComponents ${CMAKE_SOURCE_DIR}/modules/batchmake/tests/controllers/components )