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

require_once BASE_PATH.'/modules/api/library/APIEnabledNotification.php';

class Tracker_Notification extends ApiEnabled_Notification
  {
  public $moduleName = 'tracker';
  public $_moduleComponents = array('Api');

  /** init notification process*/
  public function init()
    {
    $baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
    $this->moduleWebroot = $baseUrl.'/'.$this->moduleName;
    $this->webroot = $baseUrl;

    $this->addCallBack('CALLBACK_CORE_GET_COMMUNITY_VIEW_TABS', 'communityViewTabs');
    $this->addCallBack('CALLBACK_CORE_GET_COMMUNITY_DELETED', 'communityDeleted');
    $this->addCallBack('CALLBACK_CORE_GET_ITEM_DELETED', 'itemDeleted');
    $this->enableWebAPI($this->moduleName);
    }//end init

  /**
   * Show trackers tab on the community view page
   */
  public function communityViewTabs($args)
    {
    $community = $args['community'];
    return array('Trackers' => $this->moduleWebroot.'/producer/list?communityId='.$community->getKey());
    }

  /**
   * When a community is deleted, we must delete all associated trackers
   */
  public function communityDeleted($args)
    {
    // TODO
    }

  /**
   * When an item is deleted, we must delete associated item2scalar records
   */
  public function itemDeleted($args)
    {
    // TODO
    }
  } //end class
?>
