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
  public $_models = array('Community', 'Folder', 'Group', 'Folderpolicygroup', 'Itempolicygroup', 'Group', 'User', 'Feed', "Feedpolicygroup", "Feedpolicyuser", 'Item', 'CommunityInvitation');
  public $_daos = array('Community', 'Folder', 'Group', 'Folderpolicygroup', 'Group', 'User');
  public $_components = array('Sortdao', 'Date');
  public $_forms = array('Community');

  /** Init Controller */
  function init()
    {
    $this->view->activemenu = 'community'; // set the active menu
    $actionName = Zend_Controller_Front::getInstance()->getRequest()->getActionName();
    if(isset($actionName) && (is_numeric($actionName) || strlen($actionName) == 32)) // This is tricky! and for Cassandra for now
      {
      $this->_forward('view', null, null, array('communityId' => $actionName));
      }
    }  // end init()

  /** Manage community*/
  function manageAction()
    {
    if(!$this->logged)
      {
      $this->haveToBeLogged();
      return false;
      }

    $communityId = $this->_getParam("communityId");
    if(!isset($communityId) || (!is_numeric($communityId) && strlen($communityId) != 32)) // This is tricky! and for Cassandra for now
      {
      throw new Zend_Exception("Community ID should be a number");
      }
    $communityDao = $this->Community->load($communityId);
    if($communityDao === false || !$this->Community->policyCheck($communityDao, $this->userSession->Dao, MIDAS_POLICY_WRITE))
      {
      throw new Zend_Exception("This community doesn't exist  or you don't have the permissions.");
      }
    $formInfo = $this->Form->Community->createCreateForm();
    $formCreateGroup = $this->Form->Community->createCreateGroupForm();

    //ajax posts
    if($this->_request->isPost())
      {
      $this->_helper->layout->disableLayout();
      $this->_helper->viewRenderer->setNoRender();
      $modifyInfo = $this->_getParam('modifyInfo');
      $editGroup = $this->_getParam('editGroup');
      $deleteGroup = $this->_getParam('deleteGroup');
      $addUser = $this->_getParam('addUser');
      $removeUser = $this->_getParam('removeUser');
      if(isset($removeUser)) //remove users from group
        {
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
        if($formInfo->isValid($_POST))
          {
          $communityDao = $this->Community->load($communityDao->getKey());
          $communityDao->setName($formInfo->getValue('name'));
          $communityDao->setDescription($formInfo->getValue('description'));

          // update folderpolicygroup, itempolicygroup and feedpolicygroup tables when community privacy is changed between public and private
          // users in Midas_anonymouse_group can see community's public folder only if the community is set as public
          $forminfo_privacy = $formInfo->getValue('privacy');
          $communityDao->setPrivacy($forminfo_privacy);
          $anonymousGroup = $this->Group->load(MIDAS_GROUP_ANONYMOUS_KEY);
          $communityPublicFolder = $communityDao->getPublicFolder();
          $folderpolicygroupDao = $this->Folderpolicygroup->getPolicy($anonymousGroup, $communityPublicFolder);
          if($forminfo_privacy == MIDAS_COMMUNITY_PRIVATE && $folderpolicygroupDao !== false)
            {
            // process root folder
            $this->Folderpolicygroup->delete($folderpolicygroupDao);
            // process items in root folder
            $items = $communityPublicFolder->getItems();
            foreach($items as $item)
              {
              $itemolicygroupDao = $this->Itempolicygroup->getPolicy($anonymousGroup, $item);
              $this->Itempolicygroup->delete($itemolicygroupDao);
              }
            // process all the children (and grandchildren ...) folders
            $subfolders = $this->Folder->getAllChildren($communityDao->getPublicFolder(), $this->userSession->Dao);
            foreach($subfolders as $subfolder)
              {
              $subfolderpolicygroupDao = $this->Folderpolicygroup->getPolicy($anonymousGroup, $subfolder);
              $this->Folderpolicygroup->delete($subfolderpolicygroupDao);
              // process items in children folders
              $subitems = $subfolder->getItems();
              foreach($subitems as $subfolderItem)
                {
                $subfolderitemolicygroupDao = $this->Itempolicygroup->getPolicy($anonymousGroup, $subfolderItem);
                $this->Itempolicygroup->delete($subfolderitemolicygroupDao);
                }
              }
            }
          else if($forminfo_privacy == MIDAS_COMMUNITY_PUBLIC && $folderpolicygroupDao == false)
            {
            // process root folder
            $this->Folderpolicygroup->createPolicy($anonymousGroup, $communityPublicFolder, MIDAS_POLICY_READ);
            // process items in root folder
            $items = $communityPublicFolder->getItems();
            foreach($items as $item)
              {
              $this->Itempolicygroup->createPolicy($anonymousGroup, $item, MIDAS_POLICY_READ);
              }
            // process all the children (and grandchildren ...) folders
            $subfolders = $this->Folder->getAllChildren($communityDao->getPublicFolder(), $this->userSession->Dao);
            foreach($subfolders as $subfolder)
              {
              $this->Folderpolicygroup->createPolicy($anonymousGroup, $subfolder, MIDAS_POLICY_READ);
              // process items in children folders
              $subitems = $subfolder->getItems();
              foreach($subitems as $subfolderItem)
                {
                $this->Itempolicygroup->createPolicy($anonymousGroup, $subfolderItem, MIDAS_POLICY_READ);
                }
              }
            }

          // users in Midas_anonymouse_group can see CREATE_COMMUNITY feed for this community only if the community is set as public
          $feedcreatecommunityDaoArray = array();
          // there exist 1 and only 1 feed in 'MIDAS_FEED_CREATE_COMMUNITY' type for any commuinty
          $feedcreatecommunityDaoArray = $this->Feed->getFeedByResourceAndType(MIDAS_FEED_CREATE_COMMUNITY, $communityDao);
          $feedpolicygroupDao = $this->Feedpolicygroup->getPolicy($anonymousGroup, $feedcreatecommunityDaoArray[0]);
          if($forminfo_privacy == MIDAS_COMMUNITY_PRIVATE && $feedpolicygroupDao !== false)
            {
            $this->Feedpolicygroup->delete($feedpolicygroupDao);
            }
          else if($forminfo_privacy == MIDAS_COMMUNITY_PUBLIC && $feedpolicygroupDao == false)
            {
            $this->Feedpolicygroup->createPolicy($anonymousGroup, $feedcreatecommunityDaoArray[0], MIDAS_POLICY_READ);
            }

          $communityDao->setCanJoin($formInfo->getValue('canJoin'));
          $this->Community->save($communityDao);
          echo JsonComponent::encode(array(true, $this->t('Changes saved'), $formInfo->getValue('name')));
          }
        else
          {
          echo JsonComponent::encode(array(false, $this->t('Error')));
          }
        }
      if(isset($editGroup))
        {
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

    //init forms
    $formInfo->setAction($this->view->webroot.'/community/manage?communityId='.$communityId);
    $formCreateGroup->setAction($this->view->webroot.'/community/manage?communityId='.$communityId);
    $name = $formInfo->getElement('name');
    $name->setValue($communityDao->getName());
    $description = $formInfo->getElement('description');
    $description->setValue($communityDao->getDescription());
    $privacy = $formInfo->getElement('privacy');
    $privacy->setValue($communityDao->getPrivacy());
    $canJoin = $formInfo->getElement('canJoin');
    $canJoin->setValue($communityDao->getCanJoin());
    $submit = $formInfo->getElement('submit');
    $submit->setLabel($this->t('Save'));
    $this->view->infoForm = $this->getFormAsArray($formInfo);
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
    $this->view->privateFolderId = $communityDao->getPrivatefolderId();
    $this->view->publicFolderId = $communityDao->getPublicfolderId();
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
      }
    else
      {
      $communities = $this->User->getUserCommunities($this->userSession->Dao);
      $communities = array_merge($communities, $this->Community->getPublicCommunities());
      }

    $this->Component->Sortdao->field = 'name';
    $this->Component->Sortdao->order = 'asc';
    usort($communities, array($this->Component->Sortdao, 'sortByName'));
    $communities = $this->Component->Sortdao->arrayUniqueDao($communities);

    $this->view->userCommunities = $communities;

    $this->addDynamicHelp('.communityList:first', 'List of current projects/communities hosted on MIDAS.', 'top right', 'bottom left');
    $this->addDynamicHelp('.createCommunity', 'Manage your own community or project.');
    }//end index

  /** View a community*/
  function viewAction()
    {
    $this->view->Date = $this->Component->Date;
    $communityId = $this->_getParam("communityId");
    if(!isset($communityId) || (!is_numeric($communityId) && strlen($communityId) != 32)) // This is tricky! and for Cassandra for now
      {
      throw new Zend_Exception("Community ID should be a number");
      }
    $communityDao = $this->Community->load($communityId);
    if($communityDao === false || !$this->Community->policyCheck($communityDao, $this->userSession->Dao))
      {
      throw new Zend_Exception("This community doesn't exist  or you don't have the permissions.");
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
      if($this->view->isInvited)
        {
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
    $this->view->privateFolderId = $communityDao->getPrivatefolderId();
    $this->view->publicFolderId = $communityDao->getPublicfolderId();
    $this->view->json['community'] = $communityDao->toArray();
    $this->view->json['community']['sendInvitation'] = $this->t('Send invitation');

    if($this->view->isMember)
      {
      $this->view->shareItems = $this->Item->getSharedToCommunity($communityDao);
      }

    $this->view->title .= ' - '.$communityDao->getName();
    $this->view->metaDescription = substr($communityDao->getDescription(), 0, 160);

    $this->view->customJSs = Zend_Registry::get('notifier')->callback('CALLBACK_CORE_GET_COMMUNITY_VIEW_JSS', array());
    $this->view->customCSSs = Zend_Registry::get('notifier')->callback('CALLBACK_CORE_GET_COMMUNITY_VIEW_CSSS', array());

    $this->addDynamicHelp('#tabDataLink', 'Public and Private Data hosted by the community.');
    $this->addDynamicHelp('#tabFeedLink', 'What\'s new?');
    $this->addDynamicHelp('#tabInfoLink', 'Description of the community.');
    $this->addDynamicHelp('#tabSharedLink', 'Data shared to the member of the community.');

    } //end index

  /** Delete a community*/
  function deleteAction()
    {
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();

    $communityId = $this->_getParam("communityId");
    if(!isset($communityId) || (!is_numeric($communityId) && strlen($communityId) != 32)) // This is tricky! and for Cassandra for now
      {
      throw new Zend_Exception("Community ID should be a number");
      }
    $communityDao = $this->Community->load($communityId);
    if($communityDao === false || !$this->Community->policyCheck($communityDao, $this->userSession->Dao, MIDAS_POLICY_ADMIN))
      {
      throw new Zend_Exception("This community doesn't exist or you don't have the permissions.");
      }

    $this->Community->delete($communityDao);

    $this->_redirect('/');
    }//end delete

  /** Invite a user to a community*/
  function invitationAction()
    {
    $this->_helper->layout->disableLayout();

    $communityId = $this->_getParam("communityId");
    if(!isset($communityId) || (!is_numeric($communityId) && strlen($communityId) != 32)) // This is tricky! and for Cassandra for now
      {
      throw new Zend_Exception("Community ID should be a number");
      }
    $communityDao = $this->Community->load($communityId);
    if($communityDao === false || !$this->Community->policyCheck($communityDao, $this->userSession->Dao, MIDAS_POLICY_WRITE))
      {
      throw new Zend_Exception("This community doesn't exist or you don't have the permissions.");
      }

    if($this->_request->isPost())
      {
      $this->_helper->viewRenderer->setNoRender();
      $sendInvitation = $this->_getParam('sendInvitation');
      if(isset($sendInvitation))
        {
        $userId = $this->_getParam('userId');
        $userDao = $this->User->load($userId);
        if($userDao == false)
          {
          throw new Zend_Exception("Unable to find user.");
          }
        $invitation = $this->CommunityInvitation->createInvitation($communityDao, $this->userSession->Dao, $userDao);
        if($invitation == false)
          {
          echo JsonComponent::encode(array(false, $this->t('Error')));
          }
        else
          {
          echo JsonComponent::encode(array(true, $userDao->getFullName().' '.$this->t('has been invited')));
          }
        }
      }
    }//end invite

  /** Create a community (ajax)*/
  function createAction()
    {
    if(!$this->logged)
      {
      throw new Zend_Exception("You have to be logged in to do that");
      }
    $form = $this->Form->Community->createCreateForm();
    if($this->_request->isPost() && $form->isValid($this->getRequest()->getPost()))
      {
      $name = $form->getValue('name');
      $description = $form->getValue('description');
      $privacy = $form->getValue('privacy');
      $canJoin = $form->getValue('canJoin');
      $communityDao = $this->Community->createCommunity($name, $description, $privacy, $this->userSession->Dao, $canJoin);
      $this->_redirect("/community/".$communityDao->getKey());
      }
    else
      {
      $this->requireAjaxRequest();
      $this->_helper->layout->disableLayout();
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
  }//end class
