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
class Batchmake_Notification extends MIDAS_Notification
  {
  public $_moduleComponents=array('KWBatchmake');
  public $moduleName = 'batchmake';
  public $_components = array('Utility', 'Internationalization');    
    
  /** init notification process*/
  public function init()
    {
    $this->addCallBack('CALLBACK_CORE_GET_DASHBOARD', 'getDashboard');
    }//end init


  /** generate Dashboard information */
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
  } //end class
?>
