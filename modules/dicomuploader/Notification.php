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
class Dicomuploader_Notification extends ApiEnabled_Notification
  {
  public $_moduleComponents = array('Api', 'Uploader');
  public $moduleName = 'dicomuploader';

  /** init notification process*/
  public function init()
    {
    $this->enableWebAPI($this->moduleName);
    $fc = Zend_Controller_Front::getInstance();
    $this->moduleWebroot = $fc->getBaseUrl().'/modules/'.$this->moduleName;
    $this->coreWebroot = $fc->getBaseUrl().'/core';
    $this->apiWebroot = $fc->getBaseURL().'/modules/api';

    $this->addCallBack('CALLBACK_CORE_GET_DASHBOARD', 'getDashboard');
    }//end init


  /** Add admin dashboard entry for DICOM uploader */
  public function getDashboard()
    {
    $return = $this->ModuleComponent->Uploader->isDICOMUploaderWorking();
    return $return;
    }//end _getDasboard

} //end class
