<?php
/** notification manager*/
class Communityagreement_Notification extends MIDAS_Notification
  {
  public $_models = array('Community');
  
  /** init notification process*/
  public function init()
    {
    $this->addCallBack('CALLBACK_CORE_GET_COMMUNITY_MANAGE_TABS', 'getCommunityManageTabs');
    $this->addCallBack('CALLBACK_CORE_GET_COMMUNITY_VIEW_JSS', 'getCommunityViewJSs');
    $this->addCallBack('CALLBACK_CORE_GET_COMMUNITY_VIEW_CSSS', 'getCommunityViewCSSs');
    }//end init
    
  /** get tabs */
  public function getCommunityManageTabs()
    {
    $fc = Zend_Controller_Front::getInstance();
    $moduleWebroot = $fc->getBaseUrl().'/communityagreement';
    return array('Community Agreement' => $moduleWebroot.'/config/agreementtab');
    }
  
  /** get Java scripts */  
  public function getCommunityViewJSs()
    {
    $fc = Zend_Controller_Front::getInstance();
    $moduleUriroot = $fc->getBaseUrl().'/modules/communityagreement';
    return array($moduleUriroot.'/public/js/config/config.agreementcheckbox.js');
    }
  
  /** get CSS */
  public function getCommunityViewCSSs()
    {
    $fc = Zend_Controller_Front::getInstance();
    $moduleUriroot = $fc->getBaseUrl().'/modules/communityagreement';
    return array($moduleUriroot.'/public/css/config/config.agreementcheckbox.css');
    }
    
  } //end class
?>

