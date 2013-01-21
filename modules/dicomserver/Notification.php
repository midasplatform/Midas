<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/

require_once BASE_PATH . '/modules/api/library/APIEnabledNotification.php';

/** notification manager*/
class Dicomserver_Notification extends ApiEnabled_Notification
  {
  public $_moduleComponents = array('Api', 'Server');
  public $moduleName = 'dicomserver';

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
    return '<li id="dicomRegisterListItem" style="display: none;">'.
      '<a id="dicomRegisterAction" href="#">'.
      '<img alt="" src="'.$webroot.'/modules/'.
      $this->moduleName.'/public/images/dicom_icon.jpg" /> '.
      $this->t('Register Dicom Images').'</a></li>';
    }

  /** Get javascript for the item view that will specify the ajax call
   *  for DICOM registration
   */
  public function getJs($params)
    {
    return array($this->moduleWebroot.
                 '/public/js/item/dicomserver.item.view.js',
                 $this->apiWebroot.
                 '/public/js/common/common.ajaxapi.js');
    }

  /** Add admin dashboard entry for DICOM server */
  public function getDashboard()
    {
    $return = $this->ModuleComponent->Server->isDICOMServerWorking();
    return $return;
    }//end _getDasboard

} //end class
