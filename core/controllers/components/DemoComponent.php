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

/** Demo management Componenet */
class DemoComponent extends AppComponent
  {
  /** reset database (only works with mysql)*/
  public function reset()
    {
    if(Zend_Registry::get('configGlobal')->demomode != 1)
      {
      throw new Zend_Exception("Please enable demo mode");
      }

    $db = Zend_Registry::get('dbAdapter');
    $dbname = Zend_Registry::get('configDatabase')->database->params->dbname;

    $stmt = $db->query("SELECT * FROM INFORMATION_SCHEMA.TABLES where TABLE_SCHEMA = '".$dbname."'");
    while($row = $stmt->fetch())
      {
      $db->query("DELETE FROM `".$row['TABLE_NAME']."`");
      }

    $path = BASE_PATH.'/data/assetstore';
    $dir = opendir($path);
    while($entry = readdir($dir))
      {
      if(is_dir($path.'/'.$entry) && !in_array($entry, array('.', '..')))
        {
        $this->_rrmdir($path.'/'.$entry);
        }
      }

    $path = BASE_PATH.'/data/thumbnail';
    $dir = opendir($path);
    while($entry = readdir($dir))
      {
      if(is_dir($path.'/'.$entry) && !in_array($entry, array('.', '..')))
        {
        $this->_rrmdir($path.'/'.$entry);
        }
      }

    if(file_exists(BASE_PATH.'/core/configs/ldap.local.ini'))
      {
      unlink(BASE_PATH.'/core/configs/ldap.local.ini');
      }

    $modelLoad = new MIDAS_ModelLoader();
    $userModel = $modelLoad->loadModel('User');
    $communityModel = $modelLoad->loadModel('Community');
    $assetstoreModel = $modelLoad->loadModel('Assetstore');
    $admin = $userModel->createUser('admin@kitware.com', 'admin', 'Demo', 'Administrator', 1);
    $user = $userModel->createUser('user@kitware.com', 'user', 'Demo', 'User', 0);

    $communityDao = $communityModel->createCommunity('Demo', "This is a Demo Community", MIDAS_COMMUNITY_PUBLIC, $admin, MIDAS_COMMUNITY_CAN_JOIN);

    $assetstoreDao = new AssetstoreDao();
    $assetstoreDao->setName('Default');
    $assetstoreDao->setPath(BASE_PATH.'/data/assetstore');
    $assetstoreDao->setType(MIDAS_ASSETSTORE_LOCAL);
    $assetstoreModel->save($assetstoreDao);

    $applicationConfig = parse_ini_file(BASE_PATH.'/core/configs/application.ini', true);
    $applicationConfig['global']['defaultassetstore.id'] = $assetstoreDao->getKey();
    $applicationConfig['global']['demomode'] = true;
    $applicationConfig['global']['dynamichelp'] = true;
    $applicationConfig['global']['environment'] = 'development';
    $applicationConfig['global']['application.name'] = 'MIDAS - Demo';
    $applicationConfig['global']['application.description'] = 'MIDAS integrates multimedia server technology with Kitware?s open-source ';
    $applicationConfig['global']['application.description'] .= 'data analysis and visualization clients. The server follows open standards for data storage, access and harvesting';
    $applicationConfig['global']['application.keywords'] = 'demonstration, data management, visualization';

    $enabledModules = array('visualize', 'oai', 'metadataextractor', 'api', 'scheduler', 'thumbnailcreator', 'statistics');

    foreach($enabledModules as $module)
      {
      if(file_exists(BASE_PATH.'/core/configs/'.$module.'.demo.local.ini'))
        {
        copy(BASE_PATH.'/core/configs/'.$module.'.demo.local.ini', BASE_PATH.'/core/configs/'.$module.'.local.ini');
        $applicationConfig['module'][$module] = true;
        }
      else
        {
        unlink(BASE_PATH.'/core/configs/'.$module.'.local.ini');
        }
      }

    require_once BASE_PATH.'/core/controllers/components/UtilityComponent.php';
    $utilityComponent = new UtilityComponent();
    $utilityComponent->createInitFile(BASE_PATH.'/core/configs/application.local.ini', $applicationConfig);

    $configGlobal = new Zend_Config_Ini(APPLICATION_CONFIG, 'global', true);
    Zend_Registry::set('configGlobal', $configGlobal);

    require_once BASE_PATH.'/core/controllers/components/UploadComponent.php';
    $uploadCompoenent = new UploadComponent();
    $item = $uploadCompoenent->createUploadedItem($admin, 'midasLogo.gif', BASE_PATH.'/core/public/images/midasLogo.gif', $communityDao->getPublicFolder());
    $item = $uploadCompoenent->createUploadedItem($admin, 'cow.vtp', BASE_PATH.'/core/public/demo/cow.vtp', $communityDao->getPublicFolder());
    }

  /** recursively delete a folder*/
  private function _rrmdir($dir)
    {
    if(!file_exists($dir))
      {
      return;
      }
    if(is_dir($dir))
      {
      $objects = scandir($dir);
      }

    foreach($objects as $object)
      {
      if($object != "." && $object != "..")
        {
        if(filetype($dir."/".$object) == "dir")
          {
          $this->_rrmdir($dir."/".$object);
          }
        else
          {
          unlink($dir."/".$object);
          }
        }
      }
    reset($objects);
    rmdir($dir);
    }
  } // end class