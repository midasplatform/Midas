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

/** ItemRevisionModelBase*/
class CommunityInvitationModelBase extends AppModel
{
  /** Constructor */
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'communityinvitation';
    $this->_daoName = 'CommunityInvitationDao';
    $this->_key = 'communityinvitation_id';

    $this->_mainData = array(
      'communityinvitation_id' =>  array('type' => MIDAS_DATA),
      'community_id' =>  array('type' => MIDAS_DATA),
      'user_id' =>  array('type' => MIDAS_DATA),
      'community' =>  array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Community', 'parent_column' => 'community_id', 'child_column' => 'community_id'),
      'user' =>  array('type' => MIDAS_MANY_TO_ONE, 'model' => 'User', 'parent_column' => 'user_id', 'child_column' => 'user_id'),
      );
    $this->initialize(); // required
    } // end __construct()

  /** create invitation */
  public function createInvitation($communityDao, $userDao, $invitedUserDao)
    {
    $invitations = $invitedUserDao->getInvitations();
    foreach($invitations as $invitation)
      {
      if($invitation->getCommunityId() == $communityDao->getKey())
        {
        return;
        }
      }

    Zend_Loader::loadClass('CommunityInvitationDao', BASE_PATH.'/core/models/dao');
    $invitationDao = new CommunityInvitationDao();
    $invitationDao->setCommunityId($communityDao->getKey());
    $invitationDao->setUserId($invitedUserDao->getKey());
    $this->save($invitationDao);

    $modelLoad = new MIDAS_ModelLoader();
    $feedModel = $modelLoad->loadModel('Feed');
    $feedpolicyuserModel = $modelLoad->loadModel('Feedpolicyuser');

    $feed = $feedModel->createFeed($userDao, MIDAS_FEED_COMMUNITY_INVITATION, $invitationDao, $communityDao);
    $feedpolicyuserModel->createPolicy($invitedUserDao, $feed, MIDAS_POLICY_ADMIN);
    return $invitationDao;
    }

  /** is user invited */
  public function isInvited($communityDao, $userDao)
    {
    if($userDao == null)
      {
      return false;
      }
    $invitations = $userDao->getInvitations();
    foreach($invitations as $invitation)
      {
      if($invitation->getCommunityId() == $communityDao->getKey())
        {
        return true;
        }
      }
    return false;
    }

  /** remove invitation */
  public function removeInvitation($communityDao, $userDao)
    {
    if($userDao == null)
      {
      return;
      }
    $invitations = $userDao->getInvitations();
    foreach($invitations as $invitation)
      {
      if($invitation->getCommunityId() == $communityDao->getKey())
        {
        $modelLoad = new MIDAS_ModelLoader();
        $feedModel = $modelLoad->loadModel('Feed');
        $feeds = $feedModel->getFeedByResourceAndType(array(MIDAS_FEED_COMMUNITY_INVITATION), $invitation);
        foreach($feeds as $feed)
          {
          $feedModel->delete($feed);
          }
        $this->delete($invitation);
        return true;
        }
      }
    return;
    }
  } // end class ItemRevisionModelBase
