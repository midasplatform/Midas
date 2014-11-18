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

require_once BASE_PATH.'/core/constant/global.php';
require_once BASE_PATH.'/core/constant/core.php';
require_once BASE_PATH.'/core/constant/api.php';

require_once BASE_PATH.'/notification/MIDASNotifier.php';
require_once BASE_PATH.'/notification/GlobalNotification.php';
require_once BASE_PATH.'/core/GlobalComponent.php';
require_once BASE_PATH.'/core/AppComponent.php';
require_once BASE_PATH.'/core/GlobalController.php';
require_once BASE_PATH.'/modules/GlobalModule.php';
require_once BASE_PATH.'/core/models/MIDASModel.php';

require_once BASE_PATH.'/core/models/MIDASDatabaseInterface.php';
require_once BASE_PATH.'/core/models/MIDASDatabasePdo.php';
require_once BASE_PATH.'/core/models/GlobalDao.php';

require_once BASE_PATH.'/core/models/ModelLoader.php';
require_once BASE_PATH.'/core/ComponentLoader.php';
require_once BASE_PATH.'/core/MidasLoader.php';

require_once BASE_PATH.'/core/AppController.php';
require_once BASE_PATH.'/core/AppForm.php';
require_once BASE_PATH.'/core/models/AppModel.php';
require_once BASE_PATH.'/core/models/AppDao.php';
