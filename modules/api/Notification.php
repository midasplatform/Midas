<?php
/** notification manager*/
class Api_Notification extends MIDAS_Notification
  {
  public $_models=array('User');
  
  /** init notification process*/
  public function init($type, $params)
    {
    switch ($type)
      {
      case MIDAS_NOTIFY_GET_CONFIG_TABS:
        $fc = Zend_Controller_Front::getInstance();
        $moduleWebroot = $fc->getBaseUrl().'/api';
        return array('Api' => $moduleWebroot.'/config/usertab');
        break;

      default:
        break;
      }
    }//end init  
  } //end class
?>