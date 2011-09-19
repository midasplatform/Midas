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

/** Community Controller*/
class CommunityController extends AppController
  {
  public $_models = array('Community', 'Folder', 'Group', 'Folderpolicygroup', 'Group', 'User', 'Feed', "Feedpolicygroup", "Feedpolicyuser", 'Item', 'CommunityInvitation');
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
          $communityDao->setPrivacy($formInfo->getValue('privacy'));
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

    $this->view->isAdmin = $this->Community->policyCheck($communityDao, $this->userSession->Dao, MIDAS_POLICY_ADMIN);
    $this->view->json['community'] = $communityDao->toArray();
    $this->view->json['community']['moderatorGroup'] = $moderator_group->toArray();
    $this->view->json['community']['memberGroup'] = $group_member->toArray();
    $this->view->json['community']['message']['delete'] = $this->t('Delete');
    $this->view->json['community']['message']['deleteMessage'] = $this->t('Do you really want to delete this community? It cannot be undo.');
    $this->view->json['community']['message']['deleteGroupMessage'] = $this->t('Do you really want to delete this group? It cannot be undo.');
    $this->view->json['community']['message']['infoErrorName'] = $this->t('Please, set the name.');
    $this->view->json['community']['message']['createGroup'] = $this->t('Create a group');
    $this->view->json['community']['message']['editGroup'] = $this->t('Edit a group');

    $this->view->customTabs = Zend_Registry::get('notifier')->callback('CALLBACK_CORE_GET_COMMUNITY_MANAGE_TABS', array());
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
      $this->_redirect('/');
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
    $this->view->metaDescription = substr($communityDao->getDescription(), 0, 160);

    $this->view->customJSs = Zend_Registry::get('notifier')->callback('CALLBACK_CORE_GET_COMMUNITY_VIEW_JSS', array());
    $this->view->customCSSs = Zend_Registry::get('notifier')->callback('CALLBACK_CORE_GET_COMMUNITY_VIEW_CSSS', array());
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
      if(!$this->getRequest()->isXmlHttpRequest())
        {
        throw new Zend_Exception("Why are you here ? Should be ajax.");
        }
      $this->_helper->layout->disableLayout();
      $this->view->form = $this->getFormAsArray($form);
      }
    }//end create

  /** Validate entries (ajax)*/
  public function validentryAction()
    {
    if(!$this->getRequest()->isXmlHttpRequest())
      {
      throw new Zend_Exception("Why are you here ? Should be ajax.");
      }

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