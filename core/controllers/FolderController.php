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

/** Folder Controller*/
class FolderController extends AppController
  {
  public $_models = array('Folder', 'Folder', 'Item', 'Folderpolicygroup', 'Folderpolicyuser');
  public $_daos = array('Folder', 'Folder', 'Item');
  public $_components = array('Utility', 'Date');
  public $_forms = array('Folder');

  /** Init Controller */
  function init()
    {
    $this->view->activemenu = ''; // set the active menu
    $actionName = Zend_Controller_Front::getInstance()->getRequest()->getActionName();
    if(isset($actionName) && (is_numeric($actionName) || strlen($actionName) == 32)) // This is tricky! and for Cassandra for now
      {
      $this->_forward('view', null, null, array('folderId' => $actionName));
      }
    $this->view->activemenu = 'browse'; // set the active menu
    }  // end init()


  /** Edit Folder (ajax) */
  function editAction()
    {
    $this->_helper->layout->disableLayout();
    $folder_id = $this->_getParam('folderId');
    $folder = $this->Folder->load($folder_id);
    if(!isset($folder_id))
      {
      throw new Zend_Exception("Please set the folderId.");
      }
    elseif($folder === false)
      {
      throw new Zend_Exception("The folder doesn t exist.");
      }
    elseif(!$this->Folder->policyCheck($folder, $this->userSession->Dao, MIDAS_POLICY_WRITE))
      {
      throw new Zend_Exception("Permissions error.");
      }

    if($this->_request->isPost())
      {
      $name = $this->_getParam('name');

      // Check if folder with the same name already exists for the same parent
      if($folder->getName() != $name && $this->Folder->getFolderExists($name, $folder->getParent()))
        {
        throw new Zend_Exception('This name is already used');
        }

      $description = $this->_getParam('description');
      $teaser = $this->_getParam('teaser');

      if(strlen($name) > 0)
        {
        $folder->setName($name);
        }
      $folder->setDescription($description);
      if(strlen($teaser) < 251)
        {
        $folder->setTeaser($teaser);
        }

      $this->Folder->save($folder);
      $this->_redirect('/folder/'.$folder->getKey());
      }

    $this->view->folderDao = $folder;
    $form = $this->Form->Folder->createEditForm();
    $formArray = $this->getFormAsArray($form);
    $formArray['name']->setValue($folder->getName());
    $formArray['description']->setValue($folder->getDescription());
    $formArray['teaser']->setValue($folder->getTeaser());
    $this->view->form = $formArray;
    }

  /** View Action*/
  public function viewAction()
    {
    $this->view->Date = $this->Component->Date;
    $folder_id = $this->_getParam('folderId');
    $folder = $this->Folder->load($folder_id);
    $folders = array();
    $items = array();
    $header = "";
    if(!isset($folder_id))
      {
      throw new Zend_Exception("Please set the folderId.");
      }
    elseif($folder === false)
      {
      throw new Zend_Exception("The folder doesn t exist.");
      }
    else
      {
      $folders = $this->Folder->getChildrenFoldersFiltered($folder, $this->userSession->Dao, MIDAS_POLICY_READ);
      $items = $this->Folder->getItemsFiltered($folder, $this->userSession->Dao, MIDAS_POLICY_READ);
      foreach($items as $key => $i)
        {
        $items[$key]->size = $this->Component->Utility->formatSize($i->getSizebytes());
        }
      $header .= " <li class = 'pathFolder'><img alt = '' src = '".$this->view->coreWebroot."/public/images/FileTree/folder_open.png' /><span><a href = '".$this->view->webroot."/folder/".$folder->getKey()."'>".$this->Component->Utility->sliceName($folder->getName(), 25)."</a></span></li>";
      $parent = $folder->getParent();
      while($parent !== false)
        {
        if(strpos($parent->getName(), 'community') !== false && $this->Folder->getCommunity($parent) !== false)
          {
          $community = $this->Folder->getCommunity($parent);
          $header = " <li class = 'pathCommunity'><img alt = '' src = '".$this->view->coreWebroot."/public/images/icons/community.png' /><span><a href = '".$this->view->webroot."/community/".$community->getKey()."#tabs-3'>".$this->Component->Utility->sliceName($community->getName(), 25)."</a></span></li>".$header;
          }
        elseif(strpos($parent->getName(), 'user') !== false && $this->Folder->getUser($parent) !== false)
          {
          $user = $this->Folder->getUser($parent);
          $header = " <li class = 'pathUser'><img alt = '' src = '".$this->view->coreWebroot."/public/images/icons/unknownUser-small.png' /><span><a href = '".$this->view->webroot."/user/".$user->getKey()."'>".$this->Component->Utility->sliceName($user->getFullName(), 25)."</a></span></li>".$header;

          }
        else
          {
          $header = " <li class = 'pathFolder'><img alt = '' src = '".$this->view->coreWebroot."/public/images/FileTree/directory.png' /><span><a href = '".$this->view->webroot."/folder/".$parent->getKey()."'>".$this->Component->Utility->sliceName($parent->getName(), 15)."</a></span></li>".$header;
          }
        $parent = $parent->getParent();
        }
      $header = "<ul class = 'pathBrowser'>".$header;
      $header .= "</ul>";
      }

    if(!isset($this->userSession->recentFolders))
      {
      $this->userSession->recentFolders = array();
      }
    array_push($this->userSession->recentFolders, $folder->getKey());
    if(count($this->userSession->recentFolders) > 5)
      {
      array_shift($this->userSession->recentFolders);
      }

    $this->Folder->incrementViewCount($folder);
    $this->view->mainFolder = $folder;
    $this->view->folders = $folders;
    $this->view->items = $items;
    $this->view->header = $header;

    $this->view->isModerator = $this->Folder->policyCheck($folder, $this->userSession->Dao, MIDAS_POLICY_WRITE);
    $this->view->isAdmin = $this->Folder->policyCheck($folder, $this->userSession->Dao, MIDAS_POLICY_ADMIN);

    $this->view->title .= ' - '.$folder->getName();
    $this->view->metaDescription = substr($folder->getDescription(), 0, 160);
    $this->view->json['folder'] = $folder;
    }// end View Action



  /** delete a folder (dialog,ajax only)*/
  public function deleteAction()
    {
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();
    $folder_id = $this->_getParam('folderId');
    $folder = $this->Folder->load($folder_id);
    $header = "";
    if(!isset($folder_id))
      {
      throw new Zend_Exception("Please set the folderId.");
      }
    elseif($folder === false)
      {
      throw new Zend_Exception("The folder doesn t exist.");
      }
    elseif(!$this->Folder->policyCheck($folder, $this->userSession->Dao, MIDAS_POLICY_ADMIN))
      {
      throw new Zend_Exception("Permissions error.");
      }

    $parent = $folder->getParent();
    $folderId = $folder->getFolderId();
    // User cannot delete community's root folder, the default 'Public' folder and the default 'Private' folder
    if($this->Folder->getCommunity($folder) != false)
      {
      throw new Zend_Exception("Community Root Folder. You cannot delete it.");
      }
    $communityDao = $this->Folder->getCommunity($parent);
    if($communityDao != false)
      {
      if($communityDao->getPrivatefolderId() == $folderId || $communityDao->getPublicfolderId() == $folderId)
        {
        throw new Zend_Exception("Community Default Folder. You cannot delete it.");
        }
      }

    // User cannot delete its root folder, the default 'Public' folder and the default 'Private' folder
    if($this->Folder->getUser($folder) != false)
      {
      throw new Zend_Exception("User Root Folder. You cannot delete it.");
      }
    $userDao = $this->Folder->getUser($parent);
    if($userDao != false)
      {
      if($userDao->getPrivatefolderId() == $folderId || $userDao->getPublicfolderId() == $folderId)
        {
        throw new Zend_Exception("User Default Folder. You cannot delete it.");
        }
      }
    $this->Folder->delete($folder, true);
    $folderInfo = $folder->toArray();
    echo JsonComponent::encode(array(true, $this->t('Changes saved'), $folderInfo));
    }// end deleteAction

  /** remove an item from a folder (dialog,ajax only)*/
  public function removeitemAction()
    {
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();
    $folder_id = $this->_getParam('folderId');
    $item_id = $this->_getParam('itemId');
    $folder = $this->Folder->load($folder_id);
    $item = $this->Item->load($item_id);
    $header = '';
    if(!isset($folder_id))
      {
      throw new Zend_Exception("Please set the folderId.");
      }
    if(!isset($item_id))
      {
      throw new Zend_Exception("Please set the folderId.");
      }
    elseif($folder === false)
      {
      throw new Zend_Exception("The folder doesn't exist.");
      }
    elseif($item === false)
      {
      throw new Zend_Exception("The item doesn't exist.");
      }
    elseif(!$this->Folder->policyCheck($folder, $this->userSession->Dao, MIDAS_POLICY_ADMIN))
      {
      throw new Zend_Exception('Admin permission on folder required');
      }
    elseif(!$this->Item->policyCheck($item, $this->userSession->Dao, MIDAS_POLICY_ADMIN))
      {
      throw new Zend_Exception(MIDAS_ADMIN_PRIVILEGES_REQUIRED);
      }

    $this->Folder->removeItem($folder, $item);
    echo JsonComponent::encode(array(true, $this->t('Changes saved')));
    }// end deleteAction

  /** create a folder (dialog,ajax only)*/
  public function createfolderAction()
    {
    $this->disableLayout();
    $folder_id = $this->_getParam('folderId');
    $folder = $this->Folder->load($folder_id);
    $header = "";
    $form = $this->Form->Folder->createEditForm();
    $formArray = $this->getFormAsArray($form);
    $this->view->form = $formArray;
    if(!isset($folder_id))
      {
      throw new Zend_Exception("Please set the folderId.");
      }
    elseif($folder === false)
      {
      throw new Zend_Exception("The folder doesn t exist.");
      }
    elseif(!$this->Folder->policyCheck($folder, $this->userSession->Dao, MIDAS_POLICY_WRITE))
      {
      throw new Zend_Exception("Permissions error.");
      }
    $this->view->parentFolder = $folder;
    if($this->_request->isPost())
      {
      $this->_helper->viewRenderer->setNoRender();
      $createFolder = $this->_getParam('createFolder');
      if(isset($createFolder))
        {
        $name = $this->_getParam('name');
        if(!isset($name))
          {
          echo JsonComponent::encode(array(false, $this->t('Error')));
          }
        else
          {
          // Check if folder with the same name already exists for the same parent
          if($this->Folder->getFolderExists($name, $folder))
            {
            echo JsonComponent::encode(array(false, $this->t('This name is already used')));
            return;
            }
          $new_folder = $this->Folder->createFolder($name, '', $folder);

          if($new_folder == false)
            {
            echo JsonComponent::encode(array(false, $this->t('Error')));
            }
          else
            {
            $policyGroup = $folder->getFolderpolicygroup();
            $policyUser = $folder->getFolderpolicyuser();
            foreach($policyGroup as $policy)
              {
              $group = $policy->getGroup();
              $policyValue = $policy->getPolicy();
              $this->Folderpolicygroup->createPolicy($group, $new_folder, $policyValue);
              }
            foreach($policyUser as $policy)
              {
              $user = $policy->getUser();
              $policyValue = $policy->getPolicy();
              $this->Folderpolicyuser->createPolicy($user, $new_folder, $policyValue);
              }
            if(!$this->Folder->policyCheck($new_folder, $this->userSession->Dao, MIDAS_POLICY_ADMIN))
              {
              $this->Folderpolicyuser->createPolicy($this->userSession->Dao, $new_folder, MIDAS_POLICY_ADMIN);
              }
            echo JsonComponent::encode(array(true, $this->t('Changes saved'), $folder->toArray(), $new_folder->toArray()));
            }
          }
        }
      }
    }// end createfolderAction

  }//end class