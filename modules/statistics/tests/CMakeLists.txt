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

set( module_name statistics )
to_titlecase( ${module_name} module_name_titlecase )

add_subdirectory( controllers )
add_subdirectory( models )

add_midas_style_test( Style${module_name_titlecase}AppController ${CMAKE_SOURCE_DIR}/modules/${module_name}/AppController.php )
add_midas_style_test( Style${module_name_titlecase}Controllers ${CMAKE_SOURCE_DIR}/modules/${module_name}/controllers )
add_midas_style_test( Style${module_name_titlecase}Models ${CMAKE_SOURCE_DIR}/modules/${module_name}/models )
add_midas_style_test( Style${module_name_titlecase}Notification ${CMAKE_SOURCE_DIR}/modules/${module_name}/Notification.php )

add_midas_style_test( Style${module_name_titlecase}TestsControllers ${CMAKE_SOURCE_DIR}/modules/${module_name}/tests/controllers )
add_midas_style_test( Style${module_name_titlecase}TestsModels ${CMAKE_SOURCE_DIR}/modules/${module_name}/tests/models )
