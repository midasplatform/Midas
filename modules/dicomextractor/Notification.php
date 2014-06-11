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
class Dicomextractor_Notification extends ApiEnabled_Notification
  {
  public $_moduleComponents = array('Api', 'Extractor');
  public $moduleName = 'dicomextractor';

  /** init notification process*/
  public function init()
    {
    $this->enableWebAPI($this->moduleName);
    $fc = Zend_Controller_Front::getInstance();
    $this->moduleWebroot = $fc->getBaseUrl().'/modules/'.$this->moduleName;
    $this->coreWebroot = $fc->getBaseUrl().'/core';
    $this->apiWebroot = $fc->getBaseURL().'/modules/api';

    $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_ACTIONMENU', 'getItemMenuLink');
    $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_JS', 'getJs');
    $this->addCallBack('CALLBACK_CORE_GET_DASHBOARD', 'getDashboard');
    }//end init

  /** Get the link to place in the item action menu */
  public function getItemMenuLink($params)
    {
    $webroot = Zend_Controller_Front::getInstance()->getBaseUrl();
    return '<li id="dicomExtractListItem" style="display: none;">'.
      '<a id="dicomExtractAction" href="#">'.
      '<img alt="" src="'.$webroot.'/modules/'.
      $this->moduleName.'/public/images/dicom_icon.jpg" /> '.
      $this->t('Extract Dicom Metadata').'</a></li>';
    }

  /** Get javascript for the item view that will specify the ajax call
   *  for DICOM extraction
   */
  public function getJs($params)
    {
    return array($this->moduleWebroot.
                 '/public/js/item/dicomextractor.item.view.js',
                 $this->apiWebroot.
                 '/public/js/common/common.ajaxapi.js');
    }

  /** Add admin dashboard entry for dcmtk */
  public function getDashboard()
    {
    $return = $this->ModuleComponent->Extractor->isDCMTKWorking();
    return $return;
    }//end _getDasboard
  } // end class
