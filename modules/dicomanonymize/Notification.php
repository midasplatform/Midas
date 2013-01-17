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

/** notification manager*/
class Dicomanonymize_Notification extends MIDAS_Notification
  {
  public $moduleName = 'dicomanonymize';

  /** init notification process*/
  public function init()
    {
    $fc = Zend_Controller_Front::getInstance();
    $this->webroot = $fc->getBaseUrl();
    $this->moduleWebroot = $this->webroot.'/modules/'.$this->moduleName;
    $this->coreWebroot = $this->webroot.'/core';

    $this->addCallBack('CALLBACK_CORE_GET_UPLOAD_TABS', 'getUploadTab');
    }//end init

  /** Get upload dialog tab */
  public function getUploadTab($params)
    {
    return array('DICOM' => $this->webroot.'/'.$this->moduleName.'/upload');
    }
} //end class
