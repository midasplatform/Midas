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
/**
 * Communityagreement_Notification
 *
 * notification manager
 *
 * @category   Midas modules
 * @package    communityagreement
 */
class Communityagreement_Notification extends MIDAS_Notification
  {
  public $_models = array('Community');

  /**
   * init notification process
   */
  public function init()
    {
    $this->addCallBack('CALLBACK_CORE_GET_COMMUNITY_MANAGE_TABS', 'getCommunityManageTabs');
    $this->addCallBack('CALLBACK_CORE_GET_COMMUNITY_VIEW_JSS', 'getCommunityViewJSs');
    $this->addCallBack('CALLBACK_CORE_GET_COMMUNITY_VIEW_CSSS', 'getCommunityViewCSSs');
    }//end init

  /**
   * callback function to get 'community agreement' tab
   *
   * @return array
   */
  public function getCommunityManageTabs($args)
    {
    $fc = Zend_Controller_Front::getInstance();
    $moduleWebroot = $fc->getBaseUrl().'/communityagreement';
    return array('Community Agreement' => $moduleWebroot.'/config/agreementtab');
    }

  /**
   * callback function to get java script
   *
   * @return array
   */
  public function getCommunityViewJSs()
    {
    $fc = Zend_Controller_Front::getInstance();
    $moduleUriroot = $fc->getBaseUrl().'/modules/communityagreement';
    return array($moduleUriroot.'/public/js/config/config.agreementcheckbox.js');
    }

  /**
   * callback function to get CSS
   *
   * @return array
   */
  public function getCommunityViewCSSs()
    {
    $fc = Zend_Controller_Front::getInstance();
    $moduleUriroot = $fc->getBaseUrl().'/modules/communityagreement';
    return array($moduleUriroot.'/public/css/config/config.agreementcheckbox.css');
    }

  } //end class
?>
