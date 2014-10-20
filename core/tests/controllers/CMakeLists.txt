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

add_midas_test(CoreAdminController AdminControllerTest.php)
add_midas_test(CoreAssetstoreController AssetstoreControllerTest.php)
add_midas_test(CoreBrowseController BrowseControllerTest.php)
add_midas_test(CoreCommunityController CommunityControllerTest.php)
add_midas_test(CoreFeedController FeedControllerTest.php)
add_midas_test(CoreFolderController FolderControllerTest.php)
add_midas_test(CoreImportController ImportControllerTest.php)
add_midas_test(CoreItemController ItemControllerTest.php)
add_midas_test(CoreLicensesController LicensesControllerTest.php)
add_midas_test(CoreSearchController SearchControllerTest.php)
add_midas_test(CoreShareController ShareControllerTest.php)
add_midas_test(CoreUploadDownloadController UploadDownloadControllerTest.php)
add_midas_test(CoreUserController UserControllerTest.php)

add_subdirectory(api)
add_subdirectory(components)

add_midas_style_test(StyleCoreAdminController ${CMAKE_SOURCE_DIR}/core/controllers/AdminController.php)
add_midas_style_test(StyleCoreAssetstoreController ${CMAKE_SOURCE_DIR}/core/controllers/AssetstoreController.php)
add_midas_style_test(StyleCoreBrowseController ${CMAKE_SOURCE_DIR}/core/controllers/BrowseController.php)
add_midas_style_test(StyleCoreCommunityController ${CMAKE_SOURCE_DIR}/core/controllers/CommunityController.php)
add_midas_style_test(StyleCoreControllerComponents ${CMAKE_SOURCE_DIR}/core/controllers/components)
add_midas_style_test(StyleCoreControllerForms ${CMAKE_SOURCE_DIR}/core/controllers/forms)
add_midas_style_test(StyleCoreDownloadController ${CMAKE_SOURCE_DIR}/core/controllers/DownloadController.php)
add_midas_style_test(StyleCoreErrorController ${CMAKE_SOURCE_DIR}/core/controllers/ErrorController.php)
add_midas_style_test(StyleCoreFeedController ${CMAKE_SOURCE_DIR}/core/controllers/FeedController.php)
add_midas_style_test(StyleCoreFolderController ${CMAKE_SOURCE_DIR}/core/controllers/FolderController.php)
add_midas_style_test(StyleCoreImportController ${CMAKE_SOURCE_DIR}/core/controllers/ImportController.php)
add_midas_style_test(StyleCoreIndexController ${CMAKE_SOURCE_DIR}/core/controllers/IndexController.php)
add_midas_style_test(StyleCoreInstallController ${CMAKE_SOURCE_DIR}/core/controllers/InstallController.php)
add_midas_style_test(StyleCoreItemController ${CMAKE_SOURCE_DIR}/core/controllers/ItemController.php)
add_midas_style_test(StyleCoreLicensesController ${CMAKE_SOURCE_DIR}/core/controllers/LicensesController.php)
add_midas_style_test(StyleCoreSearchController ${CMAKE_SOURCE_DIR}/core/controllers/SearchController.php)
add_midas_style_test(StyleCoreShareController ${CMAKE_SOURCE_DIR}/core/controllers/ShareController.php)
add_midas_style_test(StyleCoreUploadController ${CMAKE_SOURCE_DIR}/core/controllers/UploadController.php)
add_midas_style_test(StyleCoreUserController ${CMAKE_SOURCE_DIR}/core/controllers/UserController.php)
