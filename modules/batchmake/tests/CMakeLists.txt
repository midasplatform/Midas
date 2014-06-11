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

option( MIDAS_RUN_BATCHMAKE_TESTS "Should MIDAS run BatchMake tests?  Specific configuration is required, see modules/batchmake/tests/README." )
if(MIDAS_RUN_BATCHMAKE_TESTS)

  # functionality tests will be added by the subdirectories 
  add_subdirectory( models )
  add_subdirectory( controllers )

  # Style tests for code
  add_midas_style_test( StyleBatchmakeControllers ${CMAKE_SOURCE_DIR}/modules/batchmake/controllers )
  add_midas_style_test( StyleBatchmakeLibrary ${CMAKE_SOURCE_DIR}/modules/batchmake/library )

  add_midas_style_test( StyleBatchmakeModels ${CMAKE_SOURCE_DIR}/modules/batchmake/models )
  add_midas_style_test( StyleBatchmakeModelsBase ${CMAKE_SOURCE_DIR}/modules/batchmake/models/base )
  add_midas_style_test( StyleBatchmakeModelsDAO ${CMAKE_SOURCE_DIR}/modules/batchmake/models/dao )
  add_midas_style_test( StyleBatchmakeModelPDO ${CMAKE_SOURCE_DIR}/modules/batchmake/models/pdo )

  # Style tests for test code
  add_midas_style_test( StyleTestsBatchmakeControllers ${CMAKE_SOURCE_DIR}/modules/batchmake/tests/controllers )
  add_midas_style_test( StyleTestsBatchmakeComponents ${CMAKE_SOURCE_DIR}/modules/batchmake/tests/controllers/components )
  add_midas_style_test( StyleTestsBatchmakeLibrary ${CMAKE_SOURCE_DIR}/modules/batchmake/tests/library )
  add_midas_style_test( StyleTestsBatchmakeModels ${CMAKE_SOURCE_DIR}/modules/batchmake/tests/models )
  add_midas_style_test( StyleTestsBatchmakeModelsBase ${CMAKE_SOURCE_DIR}/modules/batchmake/tests/models/base )

endif()
