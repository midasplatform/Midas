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

/** Demo management Component */
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

    $path = UtilityComponent::getDataDirectory('assetstore');
    $dir = opendir($path);
    while($entry = readdir($dir))
      {
      if(is_dir($path.'/'.$entry) && !in_array($entry, array('.', '..')))
        {
        $this->_rrmdir($path.'/'.$entry);
        }
      }

    $path = UtilityComponent::getDataDirectory('thumbnail');
    $dir = opendir($path);
    while($entry = readdir($dir))
      {
      if(is_dir($path.'/'.$entry) && !in_array($entry, array('.', '..')))
        {
        $this->_rrmdir($path.'/'.$entry);
        }
      }

    if(file_exists(LOCAL_CONFIGS_PATH.'/ldap.local.ini'))
      {
      unlink(LOCAL_CONFIGS_PATH.'/ldap.local.ini');
      }

    $userModel = MidasLoader::loadModel('User');
    $communityModel = MidasLoader::loadModel('Community');
    $assetstoreModel = MidasLoader::loadModel('Assetstore');
    $admin = $userModel->createUser('admin@kitware.com', 'admin', 'Demo', 'Administrator', 1);
    $userModel->createUser('user@kitware.com', 'user', 'Demo', 'User', 0);

    $communityDao = $communityModel->createCommunity('Demo', "This is a Demo Community", MIDAS_COMMUNITY_PUBLIC, $admin, MIDAS_COMMUNITY_CAN_JOIN);

    $assetstoreDao = new AssetstoreDao();
    $assetstoreDao->setName('Default');
    $assetstoreDao->setPath(UtilityComponent::getDataDirectory('assetstore'));
    $assetstoreDao->setType(MIDAS_ASSETSTORE_LOCAL);
    $assetstoreModel->save($assetstoreDao);

    $options = array('allowModifications' => true);
    $config = new Zend_Config_Ini(CORE_CONFIGS_PATH.'/application.ini', null, $options);
    $config->global->defaultassetstore->id = $assetstoreDao->getKey();
    $config->global->demomode = 1;
    $config->global->dynamichelp = 1;
    $config->global->environment = 'development';
    $config->global->application->name = 'Midas Platform - Demo';
    $description = 'Midas Platform integrates multimedia server technology with Kitware\'s open-source data analysis and';
    $description .= ' visualization clients. The server follows open standards for data storage, access and harvesting';
    $config->global->application->description = $description;

    $enabledModules = array('visualize', 'oai', 'metadataextractor', 'api', 'scheduler', 'thumbnailcreator', 'statistics');
    foreach($enabledModules as $module)
      {
      if(file_exists(LOCAL_CONFIGS_PATH.'/'.$module.'.demo.local.ini'))
        {
        copy(LOCAL_CONFIGS_PATH.'/'.$module.'.demo.local.ini', LOCAL_CONFIGS_PATH.'/'.$module.'.local.ini');
        $config->module->$module = 1;
        }
      else
        {
        unlink(LOCAL_CONFIGS_PATH.'/'.$module.'.local.ini');
        }
      }

    $writer = new Zend_Config_Writer_Ini();
    $writer->setConfig($config);
    $writer->setFilename((LOCAL_CONFIGS_PATH.'/application.local.ini'));
    $writer->write();

    $configGlobal = new Zend_Config_Ini(APPLICATION_CONFIG, 'global', true);
    Zend_Registry::set('configGlobal', $configGlobal);

    require_once BASE_PATH.'/core/controllers/components/UploadComponent.php';
    $uploadComponent = new UploadComponent();
    $uploadComponent->createUploadedItem($admin, 'midasLogo.gif', BASE_PATH.'/core/public/images/midasLogo.gif', $communityDao->getPublicFolder(), null, '', true);
    $uploadComponent->createUploadedItem($admin, 'cow.vtp', BASE_PATH.'/core/public/demo/cow.vtp', $communityDao->getPublicFolder(), null, '', true);
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
