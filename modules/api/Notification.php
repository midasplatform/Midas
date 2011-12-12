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

require_once BASE_PATH . '/modules/api/library/APIEnabledNotification.php';

/** notification manager*/
class Api_Notification extends ApiEnabled_Notification
  {
  public $moduleName = 'api';
  public $_moduleComponents = array('Api');
  public $_models = array('User');

  /** init notification process*/
  public function init()
    {
    $this->addCallBack('CALLBACK_CORE_GET_CONFIG_TABS', 'getConfigTabs');
    $this->addCallBack('CALLBACK_CORE_PASSWORD_CHANGED', 'setDefaultWebApiKey');
    $this->addCallBack('CALLBACK_CORE_NEW_USER_ADDED', 'setDefaultWebApiKey');

    $this->enableWebAPI('api');
    }//end init

  /** get Config Tabs */
  public function getConfigTabs()
    {
    $fc = Zend_Controller_Front::getInstance();
    $moduleWebroot = $fc->getBaseUrl().'/api';
    return array('Api' => $moduleWebroot.'/config/usertab');
    }

  /** Reset the user's default web API key */
  public function setDefaultWebApiKey($params)
    {
    if(!isset($params['userDao']))
      {
      throw new Zend_Exception('Error: userDao parameter required');
      }
    $this->ModelLoader = new MIDAS_ModelLoader();
    $userApiModel = $this->ModelLoader->loadModel('Userapi', 'api');
    $userApiModel->createDefaultApiKey($params['userDao']);
    }
  } //end class
?>