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

/** Community Model Base*/
abstract class CommunityModelBase extends AppModel
  {
  /** constructor */
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'community';
    $this->_key = 'community_id';
    $this->_mainData = array(
      'community_id' => array('type' => MIDAS_DATA),
      'name' => array('type' => MIDAS_DATA),
      'description' => array('type' => MIDAS_DATA),
      'creation' => array('type' => MIDAS_DATA),
      'privacy' => array('type' => MIDAS_DATA),
      'folder_id' => array('type' => MIDAS_DATA),
      'admingroup_id' => array('type' => MIDAS_DATA),
      'moderatorgroup_id' => array('type' => MIDAS_DATA),
      'membergroup_id' => array('type' => MIDAS_DATA),
      'can_join' => array('type' => MIDAS_DATA),
      'view' => array('type' => MIDAS_DATA),
      'uuid' => array('type' => MIDAS_DATA),
      'folder' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Folder', 'parent_column' => 'folder_id', 'child_column' => 'folder_id'),
      'admin_group' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Group', 'parent_column' => 'admingroup_id', 'child_column' => 'group_id'),
      'moderator_group' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Group', 'parent_column' => 'moderatorgroup_id', 'child_column' => 'group_id'),
      'invitations' =>  array('type' => MIDAS_ONE_TO_MANY, 'model' => 'CommunityInvitation', 'parent_column' => 'community_id', 'child_column' => 'community_id'),
      'groups' =>  array('type' => MIDAS_ONE_TO_MANY, 'model' => 'Group', 'parent_column' => 'community_id', 'child_column' => 'community_id'),
      'member_group' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Group', 'parent_column' => 'membergroup_id', 'child_column' => 'group_id'),
      'feeds' =>  array('type' => MIDAS_MANY_TO_MANY, 'model' => 'Feed', 'table' => 'feed2community', 'parent_column' => 'community_id', 'child_column' => 'feed_id'),
      );
    $this->initialize(); // required
    } // end __construct()

  /** Returns all the public communities. Limited to 20 by default. */
  abstract function getPublicCommunities($limit = 20);
  /** Returns the community given its name */
  abstract function getByName($name);
  /** Returns all the communities */
  abstract function getAll();
  /** Returns a community by its uuid */
  abstract function getByUuid($uuid);
  /** Returns a community given its root folder */
  abstract function getByFolder($folder);

  /** save */
  public function save($dao)
    {
    if(!isset($dao->uuid) || empty($dao->uuid))
      {
      $dao->setUuid(uniqid() . md5(mt_rand()));
      }
    $name = $dao->getName();
    if(empty($name) && $name !== '0')
      {
      throw new Zend_Exception("Please set a name for the Community.");
      }
    $cleanDescription = UtilityComponent::filterHtmlTags($dao->getDescription());
    $dao->setDescription($cleanDescription);
    parent::save($dao);
    }

  /** plus one view*/
  function incrementViewCount($communityDao)
    {
    if(!$communityDao instanceof CommunityDao)
      {
      throw new Zend_Exception("Error in param: communityDao should be a CommunityDao.");
      }
    $user = Zend_Registry::get('userSession');
    if(isset($user))
      {
      if(isset($user->viewedCommunities[$communityDao->getKey()]))
        {
        return;
        }
      else
        {
        $user->viewedCommunities[$communityDao->getKey()] = true;
        }
      }
    $communityDao->view++;
    $this->save($communityDao);
    } //end incrementViewCount

  /** Create a community.
   *  privacy: MIDAS_COMMUNITY_PUBLIC, MIDAS_COMMUNITY_PRIVATE
   *  */
  function createCommunity($name, $description, $privacy, $user, $canJoin = null, $uuid = '')
    {
    $name = ucfirst($name);
    if($this->getByName($name) !== false)
      {
      throw new Zend_Exception("Community already exists.");
      }
    if(empty($name) && $name !== '0')
      {
      throw new Zend_Exception("Please set a name for the Community.");
      }

    if($canJoin == null || $privacy == MIDAS_COMMUNITY_PRIVATE)
      {
      if($privacy == MIDAS_COMMUNITY_PRIVATE)
        {
        $canJoin = MIDAS_COMMUNITY_INVITATION_ONLY;
        }
      else
        {
        $canJoin = MIDAS_COMMUNITY_CAN_JOIN;
        }
      }

    $communityDao = MidasLoader::newDao('CommunityDao');
    $communityDao->setName($name);
    $communityDao->setDescription($description);
    $communityDao->setPrivacy($privacy);
    $communityDao->setCreation(date("Y-m-d H:i:s"));
    $communityDao->setCanJoin($canJoin);
    $communityDao->setUuid($uuid);
    $this->save($communityDao);

    $folderModel = MidasLoader::loadModel('Folder');
    $groupModel = MidasLoader::loadModel('Group');
    $feedModel = MidasLoader::loadModel('Feed');
    $folderpolicygroupModel = MidasLoader::loadModel('Folderpolicygroup');
    $feedpolicyuserModel = MidasLoader::loadModel('Feedpolicyuser');
    $feedpolicygroupModel = MidasLoader::loadModel('Feedpolicygroup');

    $folderGlobal = $folderModel->createFolder('community_'.$communityDao->getKey(), '', MIDAS_FOLDER_COMMUNITYPARENT);
    if($communityDao->getPrivacy() == MIDAS_COMMUNITY_PUBLIC)
      {
      $folderPublic = $folderModel->createFolder('Public', '', $folderGlobal);
      }
    $folderPrivate = $folderModel->createFolder('Private', '', $folderGlobal);

    $adminGroup = $groupModel->createGroup($communityDao, 'Administrator');
    $moderatorsGroup = $groupModel->createGroup($communityDao, 'Moderators');
    $memberGroup = $groupModel->createGroup($communityDao, 'Members');
    $anonymousGroup = $groupModel->load(MIDAS_GROUP_ANONYMOUS_KEY);

    $communityDao->setFolderId($folderGlobal->getKey());
    $communityDao->setAdmingroupId($adminGroup->getKey());
    $communityDao->setModeratorgroupId($moderatorsGroup->getKey());
    $communityDao->setMembergroupId($memberGroup->getKey());
    $this->save($communityDao);

    if($user != null)
      {
      $groupModel->addUser($adminGroup, $user);
      $groupModel->addUser($memberGroup, $user);

      $feed = $feedModel->createFeed($user, MIDAS_FEED_CREATE_COMMUNITY, $communityDao, $communityDao);
      $feedpolicyuserModel->createPolicy($user, $feed, MIDAS_POLICY_ADMIN);
      $feedpolicygroupModel->createPolicy($adminGroup, $feed, MIDAS_POLICY_ADMIN);
      $feedpolicygroupModel->createPolicy($moderatorsGroup, $feed, MIDAS_POLICY_ADMIN);
      $feedpolicygroupModel->createPolicy($memberGroup, $feed, MIDAS_POLICY_READ);
      }

    $folderpolicygroupModel->createPolicy($adminGroup, $folderGlobal, MIDAS_POLICY_ADMIN);
    $folderpolicygroupModel->createPolicy($adminGroup, $folderPrivate, MIDAS_POLICY_ADMIN);

    $folderpolicygroupModel->createPolicy($moderatorsGroup, $folderGlobal, MIDAS_POLICY_READ);
    $folderpolicygroupModel->createPolicy($moderatorsGroup, $folderPrivate, MIDAS_POLICY_WRITE);

    $folderpolicygroupModel->createPolicy($memberGroup, $folderGlobal, MIDAS_POLICY_READ);
    $folderpolicygroupModel->createPolicy($memberGroup, $folderPrivate, MIDAS_POLICY_READ);

    if($communityDao->getPrivacy() == MIDAS_COMMUNITY_PUBLIC)
      {
      $folderpolicygroupModel->createPolicy($adminGroup, $folderPublic, MIDAS_POLICY_ADMIN);
      $folderpolicygroupModel->createPolicy($moderatorsGroup, $folderPublic, MIDAS_POLICY_WRITE);
      $folderpolicygroupModel->createPolicy($memberGroup, $folderPublic, MIDAS_POLICY_READ);
      $folderpolicygroupModel->createPolicy($anonymousGroup, $folderPublic, MIDAS_POLICY_READ);
      if($user != null)
        {
        $feedpolicygroupModel->createPolicy($anonymousGroup, $feed, MIDAS_POLICY_READ);
        }
      }
    return $communityDao;
    } // end createCommunity()

  /** Delete a community */
  function delete($communityDao)
    {
    if(!$communityDao instanceof CommunityDao)
      {
      throw new Zend_Exception("Error in param: communityDao should be a CommunityDao.");
      }
    Zend_Registry::get('notifier')->callback('CALLBACK_CORE_COMMUNITY_DELETED', array('community' => $communityDao));

    $group_model = MidasLoader::loadModel('Group');
    $groups = $group_model->findByCommunity($communityDao);
    foreach($groups as $group)
      {
      $group_model->delete($group);
      }

    $folder_model = MidasLoader::loadModel('Folder');
    $folder = $communityDao->getFolder();
    $folder_model->delete($folder);

    $feed_model = MidasLoader::loadModel('Feed');
    $feeds = $communityDao->getFeeds();
    foreach($feeds as $feed)
      {
      $feed_model->delete($feed);
      }

    $ciModel = MidasLoader::loadModel('CommunityInvitation');
    $invitations = $communityDao->getInvitations();
    foreach($invitations as $invitation)
      {
      $ciModel->delete($invitation);
      }

    parent::delete($communityDao);
    unset($communityDao->community_id);
    $communityDao->saved = false;
    }//end delete

  /** check ifthe policy is valid
  *
  * @param FolderDao $folderDao
  * @param UserDao $userDao
  * @param type $policy
  * @return  boolean
  */
  function policyCheck($communityDao, $userDao = null, $policy = 0)
    {
    if(!$communityDao instanceof CommunityDao || !is_numeric($policy))
      {
      throw new Zend_Exception("Error in param: communityDao should be a CommunityDao and policy should be numeric.");
      }
    if($userDao == null)
      {
      $userId = -1;
      }
    else if(!$userDao instanceof UserDao)
      {
      throw new Zend_Exception("Should be an user.");
      }
    else
      {
      $userId = $userDao->getUserId();
      if($userDao->isAdmin())
        {
        return true;
        }
      }

    $privacy = $communityDao->getPrivacy();
    switch($policy)
      {
      case MIDAS_POLICY_READ:
        if($privacy != MIDAS_COMMUNITY_PRIVATE)
          {
          return true;
          }
        else if($userId == -1)
          {
          return false;
          }
        else
          {
          $user_groups = $userDao->getGroups();
          $member_group = $communityDao->getMemberGroup();
          foreach($user_groups as $group)
            {
            if($group->getKey() == $member_group->getKey())
              {
              return true;
              }
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
        break;
      case MIDAS_POLICY_WRITE:
        if($userId == -1)
          {
          return false;
          }
        else
          {
          $user_groups = $userDao->getGroups();
          $moderator_group = $communityDao->getModeratorGroup();
          $admin_group = $communityDao->getAdminGroup();
          foreach($user_groups as $group)
            {
            if($group->getKey() == $moderator_group->getKey() || $group->getKey() == $admin_group->getKey())
              {
              return true;
              }
            }
          return false;
          }
        break;
      case MIDAS_POLICY_ADMIN:
        if($userId == -1)
          {
          return false;
          }
        else
          {
          $user_groups = $userDao->getGroups();
          $admin_group = $communityDao->getAdminGroup();
          foreach($user_groups as $group)
            {
            if($group->getKey() == $admin_group->getKey())
              {
              return true;
              }
            }
          return false;
          }
        break;
      default:
        return false;
      }
    } //end policyCheck

  /**
   * Count the bitstreams under this community.
   * Returns array('size'=>size_in_bytes, 'count'=>total_number_of_bitstreams)
   */
  function countBitstreams($communityDao, $userDao = null)
    {
    $folderModel = MidasLoader::loadModel('Folder');
    $folderDao = $folderModel->load($communityDao->getFolderId());

    return $folderModel->countBitstreams($folderDao, $userDao);
    } //end countBitstreams

  /**
   * set the privacy on the passed in Community to the passed in privacyCode,
   * which should be one of MIDAS_COMMUNITY_PUBLIC, MIDAS_COMMUNITY_PRIVATE.
   * @param CommunityDao $communityDao
   * @param type $privacyCode
   * @param type $userDao
   */
  function setPrivacy($communityDao, $privacyCode, $userDao)
    {
    if(!$communityDao instanceof CommunityDao)
      {
      throw new Zend_Exception("Error in param: communityDao should be a CommunityDao.");
      }
    if($privacyCode === false ||
      ($privacyCode != MIDAS_COMMUNITY_PUBLIC && $privacyCode != MIDAS_COMMUNITY_PRIVATE))
      {
      throw new Exception('invalid value for privacyCode: ' . $privacyCode);
      }

    $feedModel = MidasLoader::loadModel('Feed');
    $feedpolicygroupModel = MidasLoader::loadModel('Feedpolicygroup');
    $folderModel = MidasLoader::loadModel('Folder');
    $folderpolicygroupModel = MidasLoader::loadModel('Folderpolicygroup');
    $groupModel = MidasLoader::loadModel('Group');
    $itempolicygroupModel = MidasLoader::loadModel('Itempolicygroup');

    // update folderpolicygroup, itempolicygroup and feedpolicygroup tables when community privacy is changed between public and private
    // users in Midas_anonymouse_group can see community's public folder only if the community is set as public
    $anonymousGroup = $groupModel->load(MIDAS_GROUP_ANONYMOUS_KEY);

    // users in Midas_anonymouse_group can see CREATE_COMMUNITY feed for this community only if the community is set as public
    $feedcreatecommunityDaos = $feedModel->getFeedByResourceAndType(MIDAS_FEED_CREATE_COMMUNITY, $communityDao);
    foreach($feedcreatecommunityDaos as $feedcreatecommunityDao)
      {
      $feedpolicygroupDao = $feedpolicygroupModel->getPolicy($anonymousGroup, $feedcreatecommunityDao);
      if($privacyCode == MIDAS_COMMUNITY_PRIVATE && $feedpolicygroupDao !== false)
        {
        $feedpolicygroupModel->delete($feedpolicygroupDao);
        }
      else if($privacyCode == MIDAS_COMMUNITY_PUBLIC && $feedpolicygroupDao == false)
        {
        $feedpolicygroupModel->createPolicy($anonymousGroup, $feedcreatecommunityDao, MIDAS_POLICY_READ);
        }
      }

    $communityDao->setPrivacy($privacyCode);
    $this->save($communityDao);
    }
  } // end class
