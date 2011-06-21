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
class Visualize_Notification extends MIDAS_Notification
  {
  public $_moduleComponents=array('Main');
  public $moduleName='visualize';
  
  /** init notification process*/
  public function init()
    {
    $this->addCallBack('CALLBACK_CORE_GET_DASHBOARD', 'getDasboard');
    $this->addCallBack("CALLBACK_VISUALIZE_CAN_VISUALIZE", 'canVisualize');
    }//end init  
    
  /** can visualize?*/
  public function canVisualize($params)
    {
    return $this->ModuleComponent->Main->canVisualizeWithParaview($params['item']) ||
           $this->ModuleComponent->Main->canVisualizeMedia($params['item']) ||
           $this->ModuleComponent->Main->canVisualizeTxt($params['item']) ||
           $this->ModuleComponent->Main->canVisualizeImage($params['item']) ||
           $this->ModuleComponent->Main->canVisualizePdf($params['item']);
    }
    
  /** generate Dasboard information */
  public function getDasboard()
    {    
    $modulesConfig=Zend_Registry::get('configsModules');
    $useparaview = $modulesConfig['visualize']->useparaview;
    if(!isset($useparaview) || !$useparaview)
      {
      return false;
      }
      
    $server = true;
    
    $header = get_headers($this->getServerURL().'/PWService', 1);
    if(strpos($header[0], '404 Not Found') != false || strpos($header[0], '503 Service Temporarily Unavailable') != false)
      {
      $server = false;
      }
    
    $return = array();
    $return['ParaviewWeb Server'] = $server; 

    return $return;
    }//end _getDasboard
    
  /** get server's url */
  function getServerURL()
    {
    $currentPort = "";
    $prefix = "http://";

    if($_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443)
      {
      $currentPort = ":".$_SERVER['SERVER_PORT'];
      }
    if($_SERVER['SERVER_PORT'] == 443 || (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS'])))
      {
      $prefix = "https://";
      }
    return $prefix.$_SERVER['SERVER_NAME'].$currentPort;
    }
  } //end class
?>