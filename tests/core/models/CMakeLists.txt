add_subdirectory( base )
add_subdirectory( dao )

#Style
add_midas_style_test( StyleCoreModelsBase ${CMAKE_SOURCE_DIR}/core/models/base/ )
add_midas_style_test( StyleCoreModelsPdo ${CMAKE_SOURCE_DIR}/core/models/pdo/ )
add_midas_style_test( StyleCoreModelsPdo ${CMAKE_SOURCE_DIR}/core/models/cassandra/ )
add_midas_style_test( StyleCoreModelsDao ${CMAKE_SOURCE_DIR}/core/models/dao/ )
add_midas_style_test( StyleCoreModelsDao ${CMAKE_SOURCE_DIR}/core/models/AppDao.php )
add_midas_style_test( StyleCoreModelsDao ${CMAKE_SOURCE_DIR}/core/models/AppModel.php )
