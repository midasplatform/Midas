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

// midas core files
require_once BASE_PATH . '/notification/MIDASNotifier.php';
require_once BASE_PATH . '/notification/GlobalNotification.php';
require_once BASE_PATH . '/core/GlobalComponent.php';
require_once BASE_PATH . '/core/AppComponent.php';
require_once BASE_PATH . '/core/GlobalController.php';
require_once BASE_PATH . '/modules/GlobalModule.php';
require_once BASE_PATH . '/core/models/MIDASModel.php';

require_once BASE_PATH . '/core/models/MIDASDatabaseInterface.php';
require_once BASE_PATH . '/core/models/MIDASDatabasePdo.php';
require_once BASE_PATH . '/core/models/MIDASDatabaseCassandra.php';
require_once BASE_PATH . '/core/models/MIDASDatabaseMongo.php';
require_once BASE_PATH . '/core/models/GlobalDao.php';

include_once BASE_PATH . '/core/constant/datatype.php';
require_once BASE_PATH . '/core/models/ModelLoader.php';
require_once BASE_PATH . '/core/ComponentLoader.php';

require_once BASE_PATH.'/core/AppController.php';
require_once BASE_PATH.'/core/AppForm.php';
require_once BASE_PATH.'/core/models/AppModel.php';
require_once BASE_PATH.'/core/models/AppDao.php';

//include constant files
include_once BASE_PATH.'/core/constant/global.php';
include_once BASE_PATH.'/core/constant/error.php';
include_once BASE_PATH.'/core/constant/datatype.php';
include_once BASE_PATH.'/core/constant/community.php';
include_once BASE_PATH.'/core/constant/item.php';
include_once BASE_PATH.'/core/constant/group.php';
include_once BASE_PATH.'/core/constant/policy.php';
include_once BASE_PATH.'/core/constant/folder.php';
include_once BASE_PATH.'/core/constant/feed.php';
include_once BASE_PATH.'/core/constant/license.php';
include_once BASE_PATH.'/core/constant/metadata.php';
include_once BASE_PATH.'/core/constant/notification.php';
include_once BASE_PATH.'/core/constant/user.php';
include_once BASE_PATH.'/core/constant/resourcetype.php';
include_once BASE_PATH.'/core/constant/task.php';


?>
