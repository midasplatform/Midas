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

/** Notification manager for the demo module */
class Demo_Notification extends MIDAS_Notification
  {
  public $moduleName = 'demo';
  public $_models = array('Setting');

  /** Init notification process */
  public function init()
    {
    $fc = Zend_Controller_Front::getInstance();
    $this->webroot = $fc->getBaseUrl();
    if($this->Setting->getValueByName('enabled', $this->moduleName))
      {
      $this->addCallBack('CALLBACK_CORE_GET_FOOTER_LAYOUT', 'getFooterLayout');
      }
    }

  /** Get footer layout */
  public function getFooterLayout()
    {
    return '<script type="text/javascript" src="'.$this->webroot.'/modules/'.$this->moduleName.'/public/js/common/common.layout.js"></script>';
    }
  }
