option( MIDAS_RUN_BATCHMAKE_TESTS "Should MIDAS run BatchMake tests?  Specific configuration is required, see modules/batchmake/tests/README." )
if(MIDAS_RUN_BATCHMAKE_TESTS)
  # functionality tests will be added by the subdirectories 
  add_subdirectory( models )
  add_subdirectory( controllers )

  # Style tests for code
  add_midas_style_test( StyleBatchmakeControllers ${CMAKE_SOURCE_DIR}/modules/batchmake/controllers )
  add_midas_style_test( StyleBatchmakeComponents ${CMAKE_SOURCE_DIR}/modules/batchmake/controllers/components )
  add_midas_style_test( StyleBatchmakeModels ${CMAKE_SOURCE_DIR}/modules/batchmake/models )
  add_midas_style_test( StyleBatchmakeModelsBase ${CMAKE_SOURCE_DIR}/modules/batchmake/models/base )
  add_midas_style_test( StyleBatchmakeModelsDAO ${CMAKE_SOURCE_DIR}/modules/batchmake/models/dao )
  add_midas_style_test( StyleBatchmakeModelPDO ${CMAKE_SOURCE_DIR}/modules/batchmake/models/pdo )

  # Style tests for test code
  add_midas_style_test( StyleTestsBatchmakeControllers ${CMAKE_SOURCE_DIR}/modules/batchmake/tests/controllers )
  add_midas_style_test( StyleTestsBatchmakeComponents ${CMAKE_SOURCE_DIR}/modules/batchmake/tests/controllers/components )
  add_midas_style_test( StyleTestsBatchmakeModels ${CMAKE_SOURCE_DIR}/modules/batchmake/tests/models )
  add_midas_style_test( StyleTestsBatchmakeModelsBase ${CMAKE_SOURCE_DIR}/modules/batchmake/tests/models/base )

endif()

