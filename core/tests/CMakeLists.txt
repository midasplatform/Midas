add_subdirectory( controllers )
add_subdirectory( models )

add_midas_style_test( StyleCoreAppController ${CMAKE_SOURCE_DIR}/core/AppController.php )
add_midas_style_test( StyleCoreGlobalController ${CMAKE_SOURCE_DIR}/core/GlobalController.php )
add_midas_style_test( StyleCoreGlobalComponent ${CMAKE_SOURCE_DIR}/core/GlobalComponent.php )
add_midas_style_test( StyleCoreAppComponent ${CMAKE_SOURCE_DIR}/core/AppComponent.php )
add_midas_style_test( StyleCoreAppFilters ${CMAKE_SOURCE_DIR}/core/AppFilters.php )
add_midas_style_test( StyleCoreAppForm ${CMAKE_SOURCE_DIR}/core/AppForm.php )
add_midas_style_test( StyleCoreBootstrap ${CMAKE_SOURCE_DIR}/core/Bootstrap.php )
add_midas_style_test( StyleCoreInclude ${CMAKE_SOURCE_DIR}/core/include.php )
add_midas_style_test( StyleIndex ${CMAKE_SOURCE_DIR}/index.php )


add_midas_style_test( StyleTestsCoreControllers ${CMAKE_SOURCE_DIR}/core/tests/controllers )
add_midas_style_test( StyleTestsCoreModels ${CMAKE_SOURCE_DIR}/core/tests/models )