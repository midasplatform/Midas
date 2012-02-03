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
class Batchmake_Notification extends ApiEnabled_Notification
  {
  public $moduleName = 'batchmake';
  public $_components = array('Utility', 'Internationalization');
  public $_moduleComponents=array('KWBatchmake','Api');

  /** init notification process*/
  public function init()
    {
    $this->enableWebAPI($this->moduleName);
    $this->addCallBack('CALLBACK_CORE_GET_DASHBOARD', 'getDashboard');
    // hiding left link Batchmake icon, this isn't necessary to show
    // $this->addCallBack('CALLBACK_CORE_GET_LEFT_LINKS', 'getLeftLink');
    }//end init


  /**
   *@method getDashboard
   * will generate information about this module to display on the Dashboard
   *@return array with key being a string describing if the configuration of
   * the module is correct or not, and value being a 1/0 for the same info.
   */
  public function getDashboard()
    {
    $return = array();
    if($this->ModuleComponent->KWBatchmake->isConfigCorrect())
      {
      $return[$this->Component->Internationalization->translate(MIDAS_BATCHMAKE_CONFIG_CORRECT)] = 1;
      }
    else
      {
      $return[$this->Component->Internationalization->translate(MIDAS_BATCHMAKE_CONFIG_ERROR)] = 0;
      }
    return $return;
    }


  /**
   *@method getLeftLink
   * will generate a link for this module to be displayed in the main view.
   *@return ['batchmake' => [ link to batchmake module, module icon image path]]
   */
  public function getLeftLink()
    {
    $fc = Zend_Controller_Front::getInstance();
    $baseURL = $fc->getBaseUrl();
    $moduleWebroot = $baseURL . '/' . MIDAS_BATCHMAKE_MODULE;
    return array(ucfirst(MIDAS_BATCHMAKE_MODULE) => array($moduleWebroot . '/index',  $baseURL . '/modules/batchmake/public/images/cmake.png'));
    }

  } //end class


?>
