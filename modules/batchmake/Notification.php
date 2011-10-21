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
    $this->addCallBack('CALLBACK_CORE_GET_LEFT_LINKS', 'getLeftLink');
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
    $moduleWebroot = $fc->getBaseUrl() . MIDAS_BATCHMAKE_MODULE;
    return array(ucfirst(MIDAS_BATCHMAKE_MODULE) => array($moduleWebroot,  $moduleWebRoot . '/public/images/cmake.png'));
    }
        
  } //end class
    
    
?>
