<?php
/*=========================================================================
 MIDAS Server
 Copyright (c) Kitware SAS. 26 rue Louis Guérin. 69100 Villeurbanne, FRANCE
 All rights reserved.
 More information http://www.kitware.com

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

         http://www.apache.org/licenses/LICENSE-2.0.txt

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
=========================================================================*/
// Web API error codes
define('MIDAS_INTERNAL_ERROR', -100);
define('MIDAS_INVALID_TOKEN', -101);
define('MIDAS_UPLOAD_FAILED', -105);
define('MIDAS_UPLOAD_TOKEN_GENERATION_FAILED', -140);
define('MIDAS_INVALID_UPLOAD_TOKEN', -141);
define('MIDAS_SOURCE_OPEN_FAILED', -142);
define('MIDAS_OUTPUT_OPEN_FAILED', -143);
define('MIDAS_INVALID_PARAMETER', -150);
define('MIDAS_INVALID_POLICY', -151);
define('MIDAS_HTTP_ERROR', -153);

// List of permission scopes
define('MIDAS_API_PERMISSION_SCOPE_ALL', 0);
define('MIDAS_API_PERMISSION_SCOPE_READ_USER_INFO', 1);
define('MIDAS_API_PERMISSION_SCOPE_WRITE_USER_INFO', 2);
define('MIDAS_API_PERMISSION_SCOPE_READ_DATA', 3);
define('MIDAS_API_PERMISSION_SCOPE_WRITE_DATA', 4);
define('MIDAS_API_PERMISSION_SCOPE_ADMIN_DATA', 5);
define('MIDAS_API_PERMISSION_SCOPE_READ_GROUPS', 6);
define('MIDAS_API_PERMISSION_SCOPE_MANAGE_GROUPS', 7);
Zend_Registry::set('permissionScopeMap', array(
  MIDAS_API_PERMISSION_SCOPE_ALL => 'All permissions (total control)',
  MIDAS_API_PERMISSION_SCOPE_READ_USER_INFO => 'Get basic user information',
  MIDAS_API_PERMISSION_SCOPE_WRITE_USER_INFO => 'Edit user information',
  MIDAS_API_PERMISSION_SCOPE_READ_DATA => 'View and download private data that you can access',
  MIDAS_API_PERMISSION_SCOPE_WRITE_DATA => 'Change existing data and create new data',
  MIDAS_API_PERMISSION_SCOPE_ADMIN_DATA => 'Delete and manage permissions on data you own',
  MIDAS_API_PERMISSION_SCOPE_READ_GROUPS => 'List group membership for communities you own',
  MIDAS_API_PERMISSION_SCOPE_MANAGE_GROUPS => 'Manage groups for communities you own'
));
?>
