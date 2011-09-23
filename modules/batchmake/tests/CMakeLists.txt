option( MIDAS_RUN_BATCHMAKE_TESTS "Should MIDAS run BatchMake tests?  Specific configuration is required, see modules/batchmake/tests/README." )
if(MIDAS_RUN_BATCHMAKE_TESTS)
  add_subdirectory( controllers )
  add_midas_style_test( StyleTestsBatchmakeControllers ${CMAKE_SOURCE_DIR}/modules/batchmake/tests/controllers )
endif()

