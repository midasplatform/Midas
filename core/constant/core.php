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

define('MIDAS_ASSETSTORE_LOCAL', 0);
define('MIDAS_ASSETSTORE_REMOTE', 1);

define('MIDAS_COMMUNITY_PUBLIC', 0);
define('MIDAS_COMMUNITY_PRIVATE', 1);
define('MIDAS_COMMUNITY_CAN_JOIN', 1);
define('MIDAS_COMMUNITY_INVITATION_ONLY', 0);

define('MIDAS_DATA', 1001);
define('MIDAS_ONE_TO_MANY', 1002);
define('MIDAS_MANY_TO_ONE', 1003);
define('MIDAS_ONE_TO_ONE', 1004);
define('MIDAS_MANY_TO_MANY', 1005);

define('MIDAS_EVENT_PRIORITY_NORMAL', 1);
define('MIDAS_EVENT_PRIORITY_LOW', 0);
define('MIDAS_EVENT_PRIORITY_HIGH', 2);

define('MIDAS_FEED_CREATE_COMMUNITY', 0);
define('MIDAS_FEED_DELETE_COMMUNITY', 1);
define('MIDAS_FEED_UPDATE_COMMUNITY', 2);
define('MIDAS_FEED_COMMUNITY_INVITATION', 3);
define('MIDAS_FEED_CREATE_USER', 10);
define('MIDAS_FEED_CREATE_FOLDER', 20);
define('MIDAS_FEED_DELETE_FOLDER', 21);
define('MIDAS_FEED_CREATE_ITEM', 30);
define('MIDAS_FEED_DELETE_ITEM', 31);
define('MIDAS_FEED_CREATE_LINK_ITEM', 32);
define('MIDAS_FEED_CREATE_REVISION', 40);

define('MIDAS_FOLDER_USERPARENT', -1);
define('MIDAS_FOLDER_COMMUNITYPARENT', -2);

define('MIDAS_GROUP_ANONYMOUS_KEY', 0);
define('MIDAS_GROUP_SERVER_KEY', -1);

define('MIDAS_METADATA_TEXT', 0);
define('MIDAS_METADATA_INT', 1);
define('MIDAS_METADATA_DOUBLE', 2);
define('MIDAS_METADATA_FLOAT', 3);
define('MIDAS_METADATA_BOOLEAN', 4);
define('MIDAS_METADATA_LONG', 5);
define('MIDAS_METADATA_STRING', 6);

define('MIDAS_POLICY_READ', 0);
define('MIDAS_POLICY_WRITE', 1);
define('MIDAS_POLICY_ADMIN', 2);

define('MIDAS_PRIORITY_CRITICAL', 2);
define('MIDAS_PRIORITY_WARNING', 4);
define('MIDAS_PRIORITY_INFO', 6);
define('MIDAS_PRIORITY_DEBUG', 7);

define('MIDAS_PRIVACY_PUBLIC', 0);
define('MIDAS_PRIVACY_PRIVATE', 2);

define('MIDAS_RESOURCE_BITSTREAM', 0);
define('MIDAS_RESOURCE_ITEM', 1);
define('MIDAS_RESOURCE_USER', 2);
define('MIDAS_RESOURCE_REVISION', 3);
define('MIDAS_RESOURCE_FOLDER', 4);
define('MIDAS_RESOURCE_ASSETSTORE', 5);
define('MIDAS_RESOURCE_COMMUNITY', 6);

define('MIDAS_SIZE_B', 1);
define('MIDAS_SIZE_KB', 1000);
define('MIDAS_SIZE_MB', 1000000);
define('MIDAS_SIZE_GB', 1000000000);
define('MIDAS_SIZE_TB', 1000000000000);

define('MIDAS_TASK_ITEM_THUMBNAIL', 0);

define('MIDAS_USER_PUBLIC', 0);
define('MIDAS_USER_PRIVATE', 1);
define('MIDAS_MAXIMUM_FOLDER_NUMBERS_PER_LEVEL', 1000);
