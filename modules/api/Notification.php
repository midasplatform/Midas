<?php
/** notification manager*/
class Api_Notification extends MIDAS_Notification
  {
  public $_models=array('User');

  /** init notification process*/
  public function init()
    {
    $this->addCallBack('CALLBACK_CORE_GET_CONFIG_TABS', 'getConfigTabs');
    $this->addCallBack('CALLBACK_CORE_PASSWORD_CHANGED', 'setDefaultWebApiKey');
    $this->addCallBack('CALLBACK_CORE_NEW_USER_ADDED', 'setDefaultWebApiKey');
    }//end init

  /** get Config Tabs */
  public function getConfigTabs()
    {
    $fc = Zend_Controller_Front::getInstance();
    $moduleWebroot = $fc->getBaseUrl().'/api';
    return array('Api' => $moduleWebroot.'/config/usertab');
    }

  /** Reset the user's default web API key */
  public function setDefaultWebApiKey($params)
    {
    if(!isset($params['userDao']))
      {
      throw new Zend_Exception('Error: userDao parameter required');
      }
    $this->ModelLoader = new MIDAS_ModelLoader();
    $userApiModel = $this->ModelLoader->loadModel('Userapi', 'api');
    $userApiModel->createDefaultApiKey($params['userDao']);
    }
  } //end class
?>