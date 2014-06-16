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
add_midas_style_test( StyleCoreProductionDbProfiler ${CMAKE_SOURCE_DIR}/core/models/profiler/ProductionDbProfiler.php )
