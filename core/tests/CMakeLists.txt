#=============================================================================
# MIDAS Server
# Copyright (c) Kitware SAS. 26 rue Louis Guérin. 69100 Villeurbanne, FRANCE
# All rights reserved.
# More information http://www.kitware.com
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#         http://www.apache.org/licenses/LICENSE-2.0.txt
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
#=============================================================================

add_subdirectory( controllers )
add_subdirectory( models )

add_midas_style_test( StyleCoreApiController ${CMAKE_SOURCE_DIR}/core/ApiController.php )
add_midas_style_test( StyleCoreAppComponent ${CMAKE_SOURCE_DIR}/core/AppComponent.php )
add_midas_style_test( StyleCoreAppController ${CMAKE_SOURCE_DIR}/core/AppController.php )
add_midas_style_test( StyleCoreAppForm ${CMAKE_SOURCE_DIR}/core/AppForm.php )
add_midas_style_test( StyleCoreBootstrap ${CMAKE_SOURCE_DIR}/core/Bootstrap.php )
add_midas_style_test( StyleCoreComponentLoader ${CMAKE_SOURCE_DIR}/core/ComponentLoader.php )
add_midas_style_test( StyleCoreConstant ${CMAKE_SOURCE_DIR}/core/constant )
add_midas_style_test( StyleCoreGlobalComponent ${CMAKE_SOURCE_DIR}/core/GlobalComponent.php )
add_midas_style_test( StyleCoreGlobalController ${CMAKE_SOURCE_DIR}/core/GlobalController.php )
add_midas_style_test( StyleCoreInclude ${CMAKE_SOURCE_DIR}/core/include.php )
add_midas_style_test( StyleCoreMidasLoader ${CMAKE_SOURCE_DIR}/core/MidasLoader.php )
add_midas_style_test( StyleCoreNotification ${CMAKE_SOURCE_DIR}/core/Notification.php )
add_midas_style_test( StyleIndex ${CMAKE_SOURCE_DIR}/index.php )

add_midas_style_test( StyleCoreTestsControllers ${CMAKE_SOURCE_DIR}/core/tests/controllers )
add_midas_style_test( StyleCoreTestsModels ${CMAKE_SOURCE_DIR}/core/tests/models )
