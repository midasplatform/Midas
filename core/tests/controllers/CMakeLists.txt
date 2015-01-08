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
