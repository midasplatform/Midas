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
  public $_models = array('User');
  public $_moduleModels = array('Trend');
  public $_moduleComponents = array('Api');

  /** init notification process*/
  public function init()
    {
    $baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
    $this->moduleWebroot = $baseUrl.'/'.$this->moduleName;
    $this->webroot = $baseUrl;

    $this->addCallBack('CALLBACK_CORE_GET_COMMUNITY_VIEW_TABS', 'communityViewTabs');
    $this->addCallBack('CALLBACK_CORE_GET_COMMUNITY_DELETED', 'communityDeleted');
    $this->addCallBack('CALLBACK_CORE_COMMUNITY_DELETED', 'communityDeleted');
    $this->addCallBack('CALLBACK_CORE_ITEM_DELETED', 'itemDeleted');
    $this->addCallBack('CALLBACK_CORE_USER_DELETED', 'userDeleted');

    $this->addTask('TASK_TRACKER_SEND_THRESHOLD_NOTIFICATION', 'sendEmail', 'Send threshold violation email');
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
   * When a community is deleted, we must delete all associated producers
   */
  public function communityDeleted($args)
    {
    // TODO
    $comm = $args['community'];
    }

  /**
   * When an item is deleted, we must delete associated item2scalar records
   */
  public function itemDeleted($args)
    {
    // TODO
    $item = $args['item'];
    }

  /**
   * When a user is deleted, we should delete their threshold notifications
   */
  public function userDeleted($args)
    {
    // TODO
    $user = $args['userDao'];
    }

  /**
   * Send an email to the user that a threshold was crossed
   */
  public function sendEmail($params)
    {
    $notification = $params['notification'];
    $scalar = $params['scalar'];
    $trend = $this->Tracker_Trend->load($scalar['trend_id']);
    $user = $this->User->load($notification['recipient_id']);
    $producer = $trend->getProducer();

    if(!$user)
      {
      $this->getLogger()->warn('Attempting to send threshold notification to user id '.$notification['recipient_id'].': No such user.');
      return;
      }
    $baseUrl = UtilityComponent::getServerURL().$this->webroot;

    $text = 'Hello,<br/><br/>This email was sent because a submitted scalar value violates a threshold that you specified.<br/><br/>';
    $text .= '<b>Community:</b> <a href="'.$baseUrl.'/community/'.$producer->getCommunityId().'">'.$producer->getCommunity()->getName().'</a><br/>';
    $text .= '<b>Producer:</b> <a href="'.$baseUrl.'/tracker/producer/view?producerId='.$producer->getKey().'">'.$producer->getDisplayName().'</a><br/>';
    $text .= '<b>Trend:</b> <a href="'.$baseUrl.'/tracker/trend/view?trendId='.$trend->getKey().'">'.$trend->getDisplayName().'</a><br/>';
    $text .= '<b>Value:</b> '.$scalar['value'];

    UtilityComponent::sendEmail($user->getEmail(), 'Tracker Threshold Notification', $text);
    }
  } //end class
?>
