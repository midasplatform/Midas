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

/** Community Controller*/
class CommunityController extends AppController
  {
  public $_models = array('Community', 'Folder', 'Group', 'Folderpolicygroup', 'Itempolicygroup',
                          'Group', 'User', 'Feed', 'Feedpolicygroup', 'Feedpolicyuser',
                          'Item', 'CommunityInvitation');
  public $_daos = array('Community', 'Folder', 'Group', 'Folderpolicygroup', 'Group', 'User');
  public $_components = array('Sortdao', 'Date', 'Utility', 'Policy');
  public $_forms = array('Community');

  /** Init Controller */
  function init()
    {
    $this->view->activemenu = 'community'; // set the active menu
    $actionName = Zend_Controller_Front::getInstance()->getRequest()->getActionName();
    if(isset($actionName) && is_numeric($actionName))
      {
      $this->_forward('view', null, null, array('communityId' => $actionName));
      }
    }  // end init()

  /** Manage community*/
  function manageAction()
    {
    $communityId = $this->_getParam("communityId");
    if(!isset($communityId) || !is_numeric($communityId))
      {
      throw new Zend_Exception("Community ID should be a number");
      }
    $communityDao = $this->Community->load($communityId);
    if($communityDao === false || !$this->Community->policyCheck($communityDao, $this->userSession->Dao, MIDAS_POLICY_WRITE))
      {
      throw new Zend_Exception("This community doesn't exist or you don't have the permissions.", 403);
      }

    $infoForm = $this->Form->Community->createInfoForm($communityDao);
    $privacyForm = $this->Form->Community->createPrivacyForm($communityDao);
    $formCreateGroup = $this->Form->Community->createCreateGroupForm();

    //ajax posts
    if($this->_request->isPost())
      {
      $this->disableLayout();
      $this->disableView();
      $modifyInfo = $this->_getParam('modifyInfo');
      $modifyPrivacy = $this->_getParam('modifyPrivacy');
      $editGroup = $this->_getParam('editGroup');
      $deleteGroup = $this->_getParam('deleteGroup');
      $addUser = $this->_getParam('addUser');
      $removeUser = $this->_getParam('removeUser');
      if(isset($removeUser)) //remove users from group
        {
        if(!$this->Community->policyCheck($communityDao, $this->userSession->Dao, MIDAS_POLICY_ADMIN))
          {
          throw new Zend_Exception("Community Admin permissions required.", 403);
          }
        $group = $this->Group->load($this->_getParam('groupId'));
        if($group == false || $group->getCommunity()->getKey() != $communityDao->getKey())
          {
          echo JsonComponent::encode(array(false, $this->t('Error')));
          }
        else
          {
          $users = explode('-', $this->_getParam('users'));
          $usersDao = $this->User->load($users);
          foreach($usersDao as $userDao)
            {
            $this->Group->removeUser($group, $userDao);
            }
          echo JsonComponent::encode(array(true, $this->t('Changes saved')));
          }
        }
      if(isset($addUser)) //add users to group
        {
        if(!$this->Community->policyCheck($communityDao, $this->userSession->Dao, MIDAS_POLICY_ADMIN))
          {
          throw new Zend_Exception("Community Admin permissions required.", 403);
          }
        $group = $this->Group->load($this->_getParam('groupId'));
        if($group == false || $group->getCommunity()->getKey() != $communityDao->getKey())
          {
          echo JsonComponent::encode(array(false, $this->t('Error')));
          }
        else
          {
          $users = explode('-', $this->_getParam('users'));
          $usersDao = $this->User->load($users);
          foreach($usersDao as $userDao)
            {
            $this->Group->addUser($group, $userDao);
            }
          echo JsonComponent::encode(array(true, $this->t('Changes saved')));
          }
        }
      if(isset($modifyInfo))
        {
        if($infoForm->isValid($_POST))
          {
          $communityDao = $this->Community->load($communityDao->getKey());
          $communityDao->setName($infoForm->getValue('name'));
          $communityDao->setDescription($infoForm->getValue('description'));
          Zend_Registry::get('notifier')->callback('CALLBACK_CORE_EDIT_COMMUNITY_INFO', array('community' => $communityDao, 'params' => $this->_getAllParams()));
          $this->Community->save($communityDao);
          echo JsonComponent::encode(array(true, $this->t('Changes saved'), $infoForm->getValue('name')));
          }
        else
          {
          echo JsonComponent::encode(array(false, $this->t('Error')));
          }
        }
      if(isset($modifyPrivacy))
        {
        if(!$this->Community->policyCheck($communityDao, $this->userSession->Dao, MIDAS_POLICY_ADMIN))
          {
          throw new Zend_Exception("Community Admin permissions required.", 403);
          }
        if($privacyForm->isValid($_POST))
          {
          $communityDao = $this->Community->load($communityDao->getKey());
          $forminfo_privacy = $privacyForm->getValue('privacy');
          $this->Community->setPrivacy($communityDao, $forminfo_privacy, $this->userSession->Dao);
          $communityDao->setCanJoin($privacyForm->getValue('canJoin'));
          $this->Community->save($communityDao);
          echo JsonComponent::encode(array(true, $this->t('Changes saved')));
          }
        else
          {
          echo JsonComponent::encode(array(false, $this->t('Error')));
          }
        }
      if(isset($editGroup))
        {
        if(!$this->Community->policyCheck($communityDao, $this->userSession->Dao, MIDAS_POLICY_ADMIN))
          {
          throw new Zend_Exception("Community Admin permissions required.", 403);
          }
        if($formCreateGroup->isValid($_POST))
          {
          if($this->_getParam('groupId') == 0)
            {
            $new_group = $this->Group->createGroup($communityDao, $formCreateGroup->getValue('name'));
            echo JsonComponent::encode(array(true, $this->t('Changes saved'), $new_group->toArray()));
            }
          else
            {
            $group = $this->Group->load($this->_getParam('groupId'));
            if($group == false || $group->getCommunity()->getKey() != $communityDao->getKey())
              {
              echo JsonComponent::encode(array(false, $this->t('Error')));
              }
            $group->setName($formCreateGroup->getValue('name'));
            $this->Group->save($group);
            echo JsonComponent::encode(array(true, $this->t('Changes saved'), $group->toArray()));
            }
          }
        else
          {
          echo JsonComponent::encode(array(false, $this->t('Error')));
          }
        }

      if(isset($deleteGroup))
        {
        if(!$this->Community->policyCheck($communityDao, $this->userSession->Dao, MIDAS_POLICY_ADMIN))
          {
          throw new Zend_Exception("Community Admin permissions required.", 403);
          }
        $group = $this->Group->load($this->_getParam('groupId'));
        if($group == false || $group->getCommunity()->getKey() != $communityDao->getKey())
          {
          echo JsonComponent::encode(array(false, $this->t('Error')));
          }
        else
          {
          $this->Group->delete($group);
          echo JsonComponent::encode(array(true, $this->t('Changes saved')));
          }
        }
      return;
      }//end ajax posts

    //init and set forms
    $formCreateGroup->setAction($this->view->webroot.'/community/manage?communityId='.$communityId);
    $this->view->infoForm = $this->getFormAsArray($infoForm);
    $this->view->privacyForm = $this->getFormAsArray($privacyForm);
    $this->view->createGroupForm = $this->getFormAsArray($formCreateGroup);

    //init groups and members
    $group_member = $communityDao->getMemberGroup();
    $admin_group = $communityDao->getAdminGroup();
    $moderator_group = $communityDao->getModeratorGroup();
    $this->view->members = $group_member->getUsers();

    $this->view->memberGroup = $group_member;
    $this->view->adminGroup = $admin_group;
    $this->view->moderatorGroup = $moderator_group;
    $this->view->groups = $this->Group->findByCommunity($communityDao);

    foreach($this->view->groups as $key => $group)
      {
      if($group->getKey() == $group_member->getKey() || $group->getKey() == $admin_group->getKey() || $group->getKey() == $moderator_group->getKey())
        {
        unset($this->view->groups[$key]);
        }
      }

    //init file tree
    $this->view->mainFolder = $communityDao->getFolder();

    $this->view->folders = $this->Folder->getChildrenFoldersFiltered($this->view->mainFolder, $this->userSession->Dao, MIDAS_POLICY_READ);
    $this->view->items = $this->Folder->getItemsFiltered($this->view->mainFolder, $this->userSession->Dao, MIDAS_POLICY_READ);
    $this->view->Date = $this->Component->Date;

    $this->view->header = $this->t("Manage Community");
    $this->view->communityDao = $communityDao;

    // User's personal data, used for drag-and-drop feature
    $this->view->userPersonalmainFolder = $this->userSession->Dao->getFolder();
    $this->view->userPersonalFolders = $this->Folder->getChildrenFoldersFiltered($this->view->userPersonalmainFolder, $this->userSession->Dao, MIDAS_POLICY_READ);
    $this->view->userPersonalItems = $this->Folder->getItemsFiltered($this->view->userPersonalmainFolder, $this->userSession->Dao, MIDAS_POLICY_READ);

    $this->view->isAdmin = $this->Community->policyCheck($communityDao, $this->userSession->Dao, MIDAS_POLICY_ADMIN);
    $this->view->json['community'] = $communityDao->toArray();
    $this->view->json['community']['moderatorGroup'] = $moderator_group->toArray();
    $this->view->json['community']['memberGroup'] = $group_member->toArray();
    $this->view->json['community']['message']['delete'] = $this->t('Delete');
    $this->view->json['community']['message']['deleteMessage'] = $this->t('Do you really want to delete this community? It cannot be undone.');
    $this->view->json['community']['message']['deleteGroupMessage'] = $this->t('Do you really want to delete this group? It cannot be undone.');
    $this->view->json['community']['message']['infoErrorName'] = $this->t('Please, set the name.');
    $this->view->json['community']['message']['createGroup'] = $this->t('Create a group');
    $this->view->json['community']['message']['editGroup'] = $this->t('Edit a group');

    $this->view->customTabs = Zend_Registry::get('notifier')->callback('CALLBACK_CORE_GET_COMMUNITY_MANAGE_TABS', array('community' => $communityDao));
    }//end manageAction

  /** Index */
  function indexAction()
    {
    $this->view->header = $this->t("Communities");
    $this->view->json['community']['createCommunity'] = $this->t('Create a community');
    $this->view->json['community']['titleCreateLogin'] = $this->t('Please log in');
    $this->view->json['community']['contentCreateLogin'] = $this->t('You need to be logged in to be able to create a community.');

    if($this->logged && $this->userSession->Dao->isAdmin())
      {
      $communities = $this->Community->getAll();
      $this->view->siteAdmin = true;
      }
    else
      {
      $communities = $this->User->getUserCommunities($this->userSession->Dao);
      $communities = array_merge($communities, $this->Community->getPublicCommunities());
      $this->view->siteAdmin = false;
      }

    $this->Component->Sortdao->field = 'name';
    $this->Component->Sortdao->order = 'asc';
    usort($communities, array($this->Component->Sortdao, 'sortByName'));
    $communities = $this->Component->Sortdao->arrayUniqueDao($communities);

    $this->view->userCommunities = $communities;
    $this->view->Utility = $this->Component->Utility;

    $this->view->otherJSs = Zend_Registry::get('notifier')->callback('CALLBACK_CORE_GET_COMMUNITY_INDEX_JS');
    $this->view->otherCSSs = Zend_Registry::get('notifier')->callback('CALLBACK_CORE_GET_COMMUNITY_INDEX_CSS');
    $this->view->otherActions = Zend_Registry::get('notifier')->callback('CALLBACK_CORE_COMMUNITY_INDEX_ACTIONS');

    $this->addDynamicHelp('.communityList:first', 'List of current projects/communities hosted on MIDAS.', 'top right', 'bottom left');
    $this->addDynamicHelp('.createCommunity', 'Manage your own community or project.');
    }//end index

  /** View a community*/
  function viewAction()
    {
    $this->view->Utility = $this->Component->Utility;
    $this->view->Date = $this->Component->Date;
    $communityId = $this->_getParam("communityId");
    if(!isset($communityId) || !is_numeric($communityId))
      {
      throw new Zend_Exception("Community ID should be a number");
      }
    $communityDao = $this->Community->load($communityId);
    if($communityDao === false || !$this->Community->policyCheck($communityDao, $this->userSession->Dao))
      {
      throw new Zend_Exception("This community doesn't exist or you don't have the permissions.", 403);
      }
    $joinCommunity = $this->_getParam('joinCommunity');
    $leaveCommunity = $this->_getParam('leaveCommunity');
    $canJoin = $communityDao->getCanJoin() == MIDAS_COMMUNITY_CAN_JOIN;

    $this->view->isInvited = $this->CommunityInvitation->isInvited($communityDao, $this->userSession->Dao);
    $this->view->canJoin = $canJoin;

    if($this->userSession->Dao != null && isset($joinCommunity) && ($canJoin || $this->view->isInvited))
      {
      $member_group = $communityDao->getMemberGroup();
      $this->Group->addUser($member_group, $this->userSession->Dao);
      Zend_Registry::get('notifier')->callback('CALLBACK_CORE_USER_JOINED_COMMUNITY', array('user' => $this->userSession->Dao, 'community' => $communityDao));
      if($this->view->isInvited)
        {
        $invitationDao = $this->CommunityInvitation->isInvited($communityDao, $this->userSession->Dao, true);
        if($invitationDao->getGroupId() !== $member_group->getKey())
          {
          // If user is invited to something besides the member group, we should add them to that group also
          $this->Group->addUser($invitationDao->getGroup(), $this->userSession->Dao);
          }
        $this->CommunityInvitation->removeInvitation($communityDao, $this->userSession->Dao);
        }
      }

    if($this->userSession->Dao != null && isset($leaveCommunity))
      {
      $member_group = $communityDao->getMemberGroup();
      $this->Group->removeUser($member_group, $this->userSession->Dao);
      $this->_redirect('/community');
      }

    $this->Community->incrementViewCount($communityDao);
    $this->view->communityDao = $communityDao;
    $this->view->information = array();
    $this->view->feeds = $this->Feed->getFeedsByCommunity($this->userSession->Dao, $communityDao);

    $group_member = $communityDao->getMemberGroup();
    $this->view->members = $group_member->getUsers();

    $this->view->mainFolder = $communityDao->getFolder();
    $this->view->folders = $this->Folder->getChildrenFoldersFiltered($this->view->mainFolder, $this->userSession->Dao, MIDAS_POLICY_READ);
    $this->view->items = $this->Folder->getItemsFiltered($this->view->mainFolder, $this->userSession->Dao, MIDAS_POLICY_READ);

    $this->view->isMember = false;
    if($this->userSession->Dao != null)
      {
      foreach($this->view->members as $member)
        {
        if($member->getKey() == $this->userSession->Dao->getKey())
          {
          $this->view->isMember = true;
          break;
          }
        }
      }
    $this->view->isModerator = $this->Community->policyCheck($communityDao, $this->userSession->Dao, MIDAS_POLICY_WRITE);
    $this->view->isAdmin = $this->Community->policyCheck($communityDao, $this->userSession->Dao, MIDAS_POLICY_ADMIN);
    $this->view->json['community'] = $communityDao->toArray();
    $this->view->json['community']['sendInvitation'] = $this->t('Send invitation');

    if($this->view->isMember)
      {
      $this->view->shareItems = $this->Item->getSharedToCommunity($communityDao);
      }

    $this->view->title .= ' - '.$communityDao->getName();
    $this->view->metaDescription = substr($this->Component->Utility->markDown($communityDao->getDescription()), 0, 160);

    $this->view->customJSs = Zend_Registry::get('notifier')->callback('CALLBACK_CORE_GET_COMMUNITY_VIEW_JSS', array());
    $this->view->customCSSs = Zend_Registry::get('notifier')->callback('CALLBACK_CORE_GET_COMMUNITY_VIEW_CSSS', array());

    $this->addDynamicHelp('#tabDataLink', 'Public and Private Data hosted by the community.');
    $this->addDynamicHelp('#tabFeedLink', 'What\'s new?');
    $this->addDynamicHelp('#tabInfoLink', 'Description of the community.');
    $this->addDynamicHelp('#tabSharedLink', 'Data shared to the member of the community.');

    $this->view->extraHtml = Zend_Registry::get('notifier')->callback(
      'CALLBACK_CORE_GET_COMMUNITY_VIEW_EXTRA_HTML', array('community' => $communityDao));
    $this->view->customTabs = Zend_Registry::get('notifier')->callback('CALLBACK_CORE_GET_COMMUNITY_VIEW_TABS', array('community' => $communityDao));
    $this->view->customManageActions = Zend_Registry::get('notifier')->callback('CALLBACK_CORE_GET_COMMUNITY_VIEW_ADMIN_ACTIONS', array('community' => $communityDao));
    } //end index

  /** Delete a community*/
  function deleteAction()
    {
    $this->disableLayout();
    $this->disableView();

    $communityId = $this->_getParam("communityId");
    if(!isset($communityId) || !is_numeric($communityId))
      {
      throw new Zend_Exception("Community ID should be a number");
      }
    $communityDao = $this->Community->load($communityId);
    if($communityDao === false || !$this->Community->policyCheck($communityDao, $this->userSession->Dao, MIDAS_POLICY_ADMIN))
      {
      throw new Zend_Exception("This community doesn't exist or you don't have the permissions.", 403);
      }
    Zend_Registry::get('notifier')->callback('CALLBACK_CORE_COMMUNITY_DELETED', array('community' => $communityDao));
    $this->Community->delete($communityDao);

    $this->_redirect('/');
    }//end delete

  /**
   * Dialog for inviting a user to a community
   * @param communityId Id of the community to invite into. Write permission required.
   */
  function invitationAction()
    {
    $this->disableLayout();

    $communityId = $this->_getParam('communityId');
    $directAdd = $this->_getParam('directadd');
    if(isset($directAdd) && $directAdd == "true") $directAdd = 1;
    else $directAdd = 0;
    
    if(!isset($communityId))
      {
      throw new Zend_Exception('Must pass a communityId parameter');
      }
    $communityDao = $this->Community->load($communityId);
    if(!$communityDao)
      {
      throw new Zend_Exception('Invalid communityId', 404);
      }
    if(!$this->Community->policyCheck($communityDao, $this->userSession->Dao, MIDAS_POLICY_WRITE))
      {
      throw new Zend_Exception('Write permission required on the community', 403);
      }
      
    $this->view->directAdd = $directAdd;
    }
    
  /**
   * Ajax method for add an user to a community group
   * @param communityId Id of the community to invite into
   * @param [groupId] Id of the group to invite into.  If none is passed, uses the members group
   * @param [userId] Id of the user to invite. If not passed, must pass email parameter
   * @param [email] Email of the user to invite.  If not passed, must pass userId parameter.
                    If no such user exists, sends an email inviting the user to register and join the group.
   */
  function addusertogroupAction()
    {
    $this->disableLayout();
    $this->disableView();

    $communityId = $this->_getParam('communityId');    
    $userId = $this->_getParam('userId');

    $community = $this->Community->load($communityId);
    if(!$community || !$this->Community->policyCheck($community, $this->userSession->Dao, MIDAS_POLICY_ADMIN))
      {
      throw new Zend_Exception('Admin permission required on the community', 403);
      }

    $group = $this->Group->load($this->_getParam('groupId'));
    $user = $this->User->load($userId);
    if($group && $user)
      {
      $member_group = $community->getMemberGroup();
      $this->Group->addUser($member_group, $user);
      $this->Group->addUser($group, $user);
      echo JsonComponent::encode(array(true, 'User added.'));
      }
    else
      {
      echo JsonComponent::encode(array(false, 'Unable to add user.'));
      }
    }

  /**
   * Ajax method for sending an invitation email to a user
   * @param communityId Id of the community to invite into
   * @param [groupId] Id of the group to invite into.  If none is passed, uses the members group
   * @param [userId] Id of the user to invite. If not passed, must pass email parameter
   * @param [email] Email of the user to invite.  If not passed, must pass userId parameter.
                    If no such user exists, sends an email inviting the user to register and join the group.
   */
  function sendinvitationAction()
    {
    $this->disableLayout();
    $this->disableView();

    $communityId = $this->_getParam('communityId');    
    $userId = $this->_getParam('userId');
    $email = $this->_getParam('email');
    $directAdd = $this->_getParam('directAdd');

    $community = $this->Community->load($communityId);
    if(!$community)
      {
      throw new Zend_Exception('Invalid communityId', 404);
      }
    if(!$this->Community->policyCheck($community, $this->userSession->Dao, MIDAS_POLICY_WRITE))
      {
      throw new Zend_Exception('Write permission required on the community', 403);
      }
    $isAdmin = $this->Community->policyCheck($community, $this->userSession->Dao, MIDAS_POLICY_ADMIN);

    $groupId = $this->_getParam('groupId');
    if(isset($groupId))
      {
      $group = $this->Group->load($groupId);
      if($group->getCommunityId() != $community->getKey())
        {
        throw new Zend_Exception('Specified group is not in the specified community');
        }
      if(!$isAdmin && $groupId == $community->getAdmingroupId())
        {
        throw new Zend_Exception('Only members of the admin group may invite users to the admin group');
        }
      }
    else
      {
      $group = $community->getMemberGroup();
      }

    if(isset($userId)) // invite an existing user by id
      {
      $user = $this->User->load($userId);
      if($user == false)
        {
        throw new Zend_Exception('Invalid userId');
        }
      $this->_sendUserInvitation($user, $group);
      }
    else if(isset($email)) // invite an existing or non-existing user by email
      {
      $email = strtolower($email);
      $existingUser = $this->User->getByEmail($email);
      if($existingUser)
        {
        $this->_sendUserInvitation($existingUser, $group);
        }
      else
        {
        $newuserModel = MidasLoader::loadModel('NewUserInvitation');
        $newuserinvite = $newuserModel->createInvitation($email, $group, $this->userSession->Dao);

        $url = $this->getServerURL().$this->view->webroot;
        $subject = 'Community Invitation';
        $body = $this->userSession->Dao->getFullName().
                ' has invited you to join the <b>'.$community->getName().
                '</b> community on Midas Platform.<br/><br/>'.
                '<a href="'.$url.'/user/emailregister?email='.$email.
                '&authKey='.$newuserinvite->getAuthKey().
                '">Click here</a> to complete your user registration '.
                'if you wish to join.';
        if(UtilityComponent::sendEmail($email, $subject, $body))
          {
          $this->getLogger()->info('User '.$this->userSession->Dao->getEmail().' emailed '.$email.' to join '
            .$community->getName().' ('.$group->getName().' group)');
          }
        else
          {
          $this->getLogger()->warn('Failed sending register/community invitation email to '.$email.' for community '.$community->getName());
          }

        echo JsonComponent::encode(array(true, 'Invitation sent to '.$email));
        }
      }
    else
      {
      throw new Zend_Exception('Must pass userId or email parameter');
      }
    }

  /**
   * Helper method to create an invitaton record to the group and send an email to the existing user
   */
  private function _sendUserInvitation($userDao, $groupDao)
    {
    if($this->Group->userInGroup($userDao, $groupDao))
      {
      echo JsonComponent::encode(array(false, $userDao->getFullName().' is already a member of this community'));
      return;
      }
    $community = $groupDao->getCommunity();
    $invitation = $this->CommunityInvitation->createInvitation($groupDao, $this->userSession->Dao, $userDao);
    // Check if there is already a pending invitation
    if(!$invitation)
      {
      echo JsonComponent::encode(array(false, $userDao->getFullName().$this->t(' is already invited to this community.')));
      }
    else
      {
      $url = $this->getServerURL().$this->view->webroot;
      $subject = 'Community Invitation';
      $body = 'You have been invited to join the <b>'.$community->getName().
              '</b> community at '.$url.'.<br/><br/>'.
              '<a href="'.$url.'/community/'.$community->getKey().'">'.
              'Click here</a> to see the community, and click the "Join the community" button '.
              'if you wish to join.';
      if(UtilityComponent::sendEmail($userDao->getEmail(), $subject, $body))
        {
        $this->getLogger()->info('User '.$this->userSession->Dao->getEmail().' invited user '.$userDao->getEmail().' to '
          .$community->getName().' ('.$groupDao->getName().' group)');
        }
      else
        {
        $this->getLogger()->warn('Failed sending community invitation email to '.$email.' for community '.$community->getName());
        }
      echo JsonComponent::encode(array(true, $userDao->getFullName().' '.$this->t('has been invited')));
      }
    }

  /** Create a community (ajax)*/
  function createAction()
    {
    $this->requireAdminPrivileges();
    $form = $this->Form->Community->createCreateForm();
    if($this->_request->isPost() && $form->isValid($this->getRequest()->getPost()))
      {
      $name = $form->getValue('name');
      $description = $form->getValue('description');
      $privacy = $form->getValue('privacy');
      $canJoin = $form->getValue('canJoin');
      $communityDao = $this->Community->createCommunity($name, $description, $privacy, $this->userSession->Dao, $canJoin);
      $this->_redirect('/community/'.$communityDao->getKey());
      }
    else
      {
      $this->disableLayout();
      $this->view->form = $this->getFormAsArray($form);
      }
    }//end create

  /** Validate entries (ajax)*/
  public function validentryAction()
    {
    $this->requireAjaxRequest();
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();
    $entry = $this->_getParam("entry");
    $type = $this->_getParam("type");
    if(!is_string($entry) || !is_string($type))
      {
      echo 'false';
      return;
      }
    switch($type)
      {
      case 'dbcommunityname':
        $communityDao = $this->Community->getByName($entry);
        if($communityDao != false)
          {
          echo "true";
          }
        else
          {
          echo "false";
          }
        return;
      default:
        echo "false";
        return;
      }
    } //end valid entry

  /** Show the dialog for adding a member to groups */
  public function promotedialogAction()
    {
    $this->disableLayout();

    if(!$this->logged)
      {
      throw new Zend_Exception('Must be logged in');
      }

    $commId = $this->_getParam('community');
    $userId = $this->_getParam('user');
    if(!isset($commId))
      {
      throw new Zend_Exception('Must pass a community parameter');
      }
    if(!isset($userId))
      {
      throw new Zend_Exception('Must pass a user parameter');
      }

    $community = $this->Community->load($commId);
    $user = $this->User->load($userId);
    if(!$user || !$community)
      {
      throw new Zend_Exception('Invalid user or community parameter');
      }
    if(!$this->Group->userInGroup($user, $community->getMemberGroup()))
      {
      throw new Zend_Exception('User is not in community members group');
      }
    if(!$this->Community->policyCheck($community, $this->userSession->Dao, MIDAS_POLICY_ADMIN))
      {
      throw new Zend_Exception('Community Admin permissions required.', 403);
      }
    $groups = $community->getGroups();
    $availableGroups = array();
    foreach($groups as $group)
      {
      if(!$this->Group->userInGroup($user, $group))
        {
        $availableGroups[] = $group; // only show groups they aren't already in
        }
      }
    $this->view->availableGroups = $availableGroups;
    $this->view->user = $user;
    $this->view->community = $community;
    }

  /**
   * Submitted by the promotedialog view; actually performs the logic of
   * adding users to groups
   */
  public function promoteuserAction()
    {
    $this->disableLayout();
    $this->_helper->viewRenderer->setNoRender();

    if(!$this->logged)
      {
      throw new Zend_Exception('Must be logged in');
      }
    $commId = $this->_getParam('communityId');
    $userId = $this->_getParam('userId');
    if(!isset($commId))
      {
      throw new Zend_Exception('Must pass a communityId parameter');
      }
    if(!isset($userId))
      {
      throw new Zend_Exception('Must pass a userId parameter');
      }

    $community = $this->Community->load($commId);
    $user = $this->User->load($userId);
    if(!$user || !$community)
      {
      throw new Zend_Exception('Invalid user or community parameter');
      }
    if(!$this->Group->userInGroup($user, $community->getMemberGroup()))
      {
      throw new Zend_Exception('User is not in community members group');
      }
    if(!$this->Community->policyCheck($community, $this->userSession->Dao, MIDAS_POLICY_ADMIN))
      {
      throw new Zend_Exception('Community Admin permissions required.', 403);
      }
    $params = $this->_getAllParams();
    foreach($params as $name => $value)
      {
      if(strpos($name, 'groupCheckbox') !== false && $value)
        {
        list(, $id) = explode('_', $name);
        $group = $this->Group->load($id);
        if(!$group)
          {
          throw new Zend_Exception('Invalid group id: '.$id);
          }
        $this->Group->addUser($group, $user);
        }
      }
    echo JsonComponent::encode(array(true, 'Successfully added user to groups'));
    }

  /**
   * Remove a user from a group
   */
  public function removeuserfromgroupAction()
    {
    $this->disableLayout();
    $this->_helper->viewRenderer->setNoRender();

    if(!$this->logged)
      {
      throw new Zend_Exception('Must be logged in');
      }
    $groupId = $this->_getParam('groupId');
    $userId = $this->_getParam('userId');
    if(!isset($groupId))
      {
      throw new Zend_Exception('Must pass a groupId parameter');
      }
    if(!isset($userId))
      {
      throw new Zend_Exception('Must pass a userId parameter');
      }

    $group = $this->Group->load($groupId);
    $user = $this->User->load($userId);
    if(!$user || !$group)
      {
      throw new Zend_Exception('Invalid user or group parameter');
      }
    $community = $group->getCommunity();

    if(!$this->Community->policyCheck($community, $this->userSession->Dao, MIDAS_POLICY_ADMIN))
      {
      throw new Zend_Exception('Community Admin permissions required.', 403);
      }
    $this->Group->removeUser($group, $user);
    echo JsonComponent::encode(array(true, 'Removed user '.$user->getFullName().' from group '.$group->getName()));
    }

  /**
   * Show dialog for selecting a group from the community.
   * Requires moderator or admin permission on the community
   * @param communityId The id of the community
   */
  public function selectgroupAction()
    {
    $this->disableLayout();

    $communityId = $this->_getParam('communityId');

    if(!isset($communityId))
      {
      throw new Zend_Exception('Community id parameter required');
      }
    $community = $this->Community->load($communityId);
    if(!$community)
      {
      throw new Zend_Exception('Community '.$communityId.' does not exist', 404);
      }
    if(!$this->Community->policyCheck($community, $this->userSession->Dao, MIDAS_POLICY_WRITE))
      {
      throw new Zend_Exception('Moderator or admin privileges required', 403);
      }
    $isAdmin = $this->Community->policyCheck($community, $this->userSession->Dao, MIDAS_POLICY_ADMIN);

    $this->view->groups = array($community->getMemberGroup(), $community->getModeratorGroup());
    if($isAdmin)
      {
      $this->view->groups[] = $community->getAdminGroup();
      }
    $allGroups = $community->getGroups();
    foreach($allGroups as $group)
      {
      if($group->getKey() != $community->getMembergroupId() &&
         $group->getKey() != $community->getModeratorgroupId() &&
         $group->getKey() != $community->getAdmingroupId())
        {
        $this->view->groups[] = $group;
        }
      }
    }
  } // end class
