#=============================================================================
# Midas Server
# Copyright Kitware SAS, 26 rue Louis Guérin, 69100 Villeurbanne, France.
# All rights reserved.
# For more information visit http://www.kitware.com/.
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

set(module_name api)
to_titlecase(${module_name} module_name_titlecase)

add_midas_test(${module_name_titlecase}CallCommunityMethodsTest CallCommunityMethodsTest.php)
add_midas_test(${module_name_titlecase}CallFolderMethodsTest CallFolderMethodsTest.php)
add_midas_test(${module_name_titlecase}CallGroupMethodsTest CallGroupMethodsTest.php)
add_midas_test(${module_name_titlecase}CallItemMethodsTest CallItemMethodsTest.php)
add_midas_test(${module_name_titlecase}CallMiscMethodsTest CallMiscMethodsTest.php)
add_midas_test(${module_name_titlecase}CallUserMethodsTest CallUserMethodsTest.php)
add_midas_test(${module_name_titlecase}IndexControllerTest IndexControllerTest.php)
add_midas_test(${module_name_titlecase}KeyControllerTest KeyControllerTest.php)
