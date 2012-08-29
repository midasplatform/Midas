add_subdirectory( base )
add_subdirectory( dao )

#Style
add_midas_style_test( StyleCoreModelsBase ${CMAKE_SOURCE_DIR}/core/models/base/ )
add_midas_style_test( StyleCoreModelsPdo ${CMAKE_SOURCE_DIR}/core/models/pdo/ )
add_midas_style_test( StyleCoreModelsDao ${CMAKE_SOURCE_DIR}/core/models/dao/ )
add_midas_style_test( StyleCoreModelsAppDao ${CMAKE_SOURCE_DIR}/core/models/AppDao.php )
add_midas_style_test( StyleCoreModelsAppModel ${CMAKE_SOURCE_DIR}/core/models/AppModel.php )

add_midas_style_test( StyleCoreMIDASDatabasePdo ${CMAKE_SOURCE_DIR}/core/models/MIDASDatabasePdo.php )
add_midas_style_test( StyleCoreMIDASDatabaseMongo ${CMAKE_SOURCE_DIR}/core/models/MIDASDatabaseMongo.php )
add_midas_style_test( StyleCoreMIDASDatabaseInterface ${CMAKE_SOURCE_DIR}/core/models/MIDASDatabaseInterface.php )
add_midas_style_test( StyleCoreMIDASModel ${CMAKE_SOURCE_DIR}/core/models/MIDASModel.php )
add_midas_style_test( StyleCoreMIDASUpgrade ${CMAKE_SOURCE_DIR}/core/models/MIDASUpgrade.php )
add_midas_style_test( StyleCoreProductionDbProfiler ${CMAKE_SOURCE_DIR}/core/models/prfiler/ProductionDbProfiler.php )
