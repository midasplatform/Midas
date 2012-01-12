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
 *  AJAX request for the admin Controller
 */
class BrowseController extends AppController
{
  public $_models = array('User', 'Community', 'Folder', 'Item');
  public $_daos = array('User', 'Community', 'Folder', 'Item');
  public $_components = array('Date', 'Utility', 'Sortdao');

  /** Init Controller */
  function init()
    {
    $this->view->activemenu = 'browse'; // set the active menu
    }  // end init()

  /** Index Action*/
  public function indexAction()
    {
    $items = array();
    $header = "";

    $this->view->Date = $this->Component->Date;

    $this->view->header = $this->t('Explore');

    $this->view->itemThumbnails = $this->Item->getRandomThumbnails($this->userSession->Dao, 0, 12, true);

    $this->view->items = $this->Item->getMostPopulars($this->userSession->Dao, 30);

    $this->view->nUsers = $this->User->getCountAll();
    $this->view->nCommunities = $this->Community->getCountAll();
    $this->view->nItems = $this->Item->getCountAll();

    $this->view->json['community']['titleCreateLogin'] = $this->t('Please log in');
    $this->view->json['community']['contentCreateLogin'] = $this->t('You need to be logged in to be able to create a community.');
    }

  /** move or copy selected element*/
  public function movecopyAction()
    {
    $shareSubmit = $this->_getParam('shareElement');
    $duplicateSubmit = $this->_getParam('duplicateElement');
    $moveSubmit = $this->_getParam('moveElement');

    $select = $this->_getParam('selectElement');
    $share = $this->_getParam('share');
    $duplicate = $this->_getParam('duplicate');

    // used for drag-and-drop actions
    if(isset($moveSubmit) || isset($shareSubmit) || isset($duplicateSubmit))
      {
      $elements = explode(';', $this->_getParam('elements'));
      $destination = $this->_getParam('destination');
      $ajax = $this->_getParam('ajax');
      $folderIds = explode('-', $elements[0]);
      $itemIds = explode('-', $elements[1]);
      $folders = $this->Folder->load($folderIds);
      $items = $this->Item->load($itemIds);
      $destinationFolder = $this->Folder->load($destination);
      if(empty($folders) && empty ($items))
        {
        throw new Zend_Exception("No element selected");
        }
      if($destinationFolder == false)
        {
        throw new Zend_Exception("Unable to load destination");
        }

      foreach($folders as $folder)
        {
        if(isset($moveSubmit))
          {
          $this->Folder->move($folder, $destinationFolder);
          }
        }

      $sourceFolderIds = array();
      foreach($items as $item)
        {
        if(isset($shareSubmit))
          {
          foreach($item->getFolders() as $parentFolder)
            {
            $folderId = $parentFolder->getKey();
            array_push($sourceFolderIds, $folderId);
            }
          if(in_array($destinationFolder->getKey(), $sourceFolderIds))
            {
            $this->_redirect('/item/'.$item->getKey());
            }
          else
            {
            $this->Folder->addItem($destinationFolder, $item);
            $this->Item->addReadonlyPolicy($item, $destinationFolder);
            }
          }
        elseif(isset($duplicateSubmit))
          {
          $this->Item->duplicateItem($item, $this->userSession->Dao, $destinationFolder);
          }
        else //moveSubmit
          {
          $from = $this->_getParam('from');
          $from = $this->Folder->load($from);
          if($destinationFolder == false)
            {
            throw new Zend_Exception("Unable to load destination");
            }
          $this->Folder->addItem($destinationFolder, $item);
          $this->Item->copyParentPolicies($item, $destinationFolder);
          $this->Folder->removeItem($from, $item);
          }
        }
      if(isset($ajax))
        {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        echo JsonComponent::encode(array(true, $this->t('Changes saved')));
        return;
        }
      $this->_redirect('/folder/'.$destinationFolder->getKey());
      }

    // Used for movecopy dialog
    $this->requireAjaxRequest();
    $this->_helper->layout->disableLayout();

    if(isset($share) || isset($duplicate))
      {
      $folderIds = $this->_getParam('folders');
      $itemIds = $this->_getParam('items');
      $this->view->folderIds = $folderIds;
      $this->view->itemIds = $itemIds;
      $folderIds = explode('-', $folderIds);
      $itemIds = explode('-', $itemIds);
      $folders = $this->Folder->load($folderIds);
      $items = $this->Item->load($itemIds);
      if(empty($folders) && empty ($items))
        {
        throw new Zend_Exception("No element selected");
        }
      if(!$this->logged)
        {
        throw new Zend_Exception(MIDAS_LOGIN_REQUIRED);
        }
      $this->view->folders = $folders;
      $this->view->items = $items;
      if(isset($share))
        {
        $this->view->shareEnabled = true;
        $this->view->duplicateEnabled = false;
        }
      else
        {
        $this->view->duplicateEnabled = true;
        $this->view->shareEnabled = false;
        }
      $this->view->selectEnabled = false;
      }
    else //isset($select)
      {
      $this->view->selectEnabled = true;
      }

    $communities = $this->User->getUserCommunities($this->userSession->Dao);
    $communities = array_merge($communities, $this->Community->getPublicCommunities());
    $this->view->Date = $this->Component->Date;

    $this->Component->Sortdao->field = 'name';
    $this->Component->Sortdao->order = 'asc';
    usort($communities, array($this->Component->Sortdao, 'sortByName'));
    $communities = $this->Component->Sortdao->arrayUniqueDao($communities );

    $this->view->user = $this->userSession->Dao;
    $this->view->communities = $communities;
    }

  /** Ajax element used to select an item*/
  public function selectitemAction()
    {
    $this->requireAjaxRequest();
    $this->_helper->layout->disableLayout();

    $this->view->selectEnabled = true;

    $communities = $this->User->getUserCommunities($this->userSession->Dao);
    $communities = array_merge($communities, $this->Community->getPublicCommunities());
    $this->view->Date = $this->Component->Date;

    $this->Component->Sortdao->field = 'name';
    $this->Component->Sortdao->order = 'asc';
    usort($communities, array($this->Component->Sortdao, 'sortByName'));
    $communities = $this->Component->Sortdao->arrayUniqueDao($communities );

    $this->view->user = $this->userSession->Dao;
    $this->view->communities = $communities;
    }

  /** Ajax element used to select a folder*/
  public function selectfolderAction()
    {
    $this->requireAjaxRequest();
    $this->disableLayout();
    $policy = $this->_getParam("policy");

    $communities = $this->User->getUserCommunities($this->userSession->Dao);


    if(isset($policy) && $policy == 'read')
      {
      $policy = MIDAS_POLICY_READ;
      $communities = array_merge($communities, $this->Community->getPublicCommunities());
      }
    else
      {
      $policy = MIDAS_POLICY_WRITE;
      }

    $this->view->selectEnabled = true;

    $this->view->Date = $this->Component->Date;
    $this->view->policy = $policy;

    $this->Component->Sortdao->field = 'name';
    $this->Component->Sortdao->order = 'asc';
    usort($communities, array($this->Component->Sortdao, 'sortByName'));
    $communities = $this->Component->Sortdao->arrayUniqueDao($communities );

    $this->view->user = $this->userSession->Dao;
    $this->view->communities = $communities;
    }

  /** get getfolders content (ajax function for the treetable) */
  public function getfolderscontentAction()
    {
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();
    $folderIds = $this->_getParam('folders');
    if(!isset($folderIds))
      {
      throw new Zend_Exception("Please set the folder Id");
      }
    $folderIds = explode('-', $folderIds);
    $parents = $this->Folder->load($folderIds);
    if(empty($parents))
      {
      throw new Zend_Exception("Folder doesn't exist");
      }

    $folders = $this->Folder->getChildrenFoldersFiltered($parents, $this->userSession->Dao, MIDAS_POLICY_READ);
    $items = $this->Folder->getItemsFiltered($parents, $this->userSession->Dao, MIDAS_POLICY_READ);
    $jsonContent = array();
    foreach($parents as $parent)
      {
      $jsonContent[$parent->getKey()]['folders'] = array();
      $jsonContent[$parent->getKey()]['items'] = array();
      }
    foreach($folders as $folder)
      {
      $tmp = array();
      $tmp['folder_id'] = $folder->getFolderId();
      $tmp['name'] = $folder->getName();
      $tmp['date_update'] = $this->Component->Date->ago($folder->getDateUpdate(), true);
      // this ajax function is only used by treetable.js and it will handle all the other folders except for the top level folders.
      // All the non-top level folders are deletable if users have correct permission
      $tmp['deletable'] =  'true';
      $tmp['policy'] = $folder->policy;
      $tmp['privacy_status'] = $folder->privacy_status;
      $jsonContent[$folder->getParentId()]['folders'][] = $tmp;
      unset($tmp);
      }
    foreach($items as $item)
      {
      $tmp = array();
      $tmp['item_id'] = $item->getItemId();
      $tmp['name'] = $item->getName();
      $tmp['parent_id'] = $item->parent_id;
      $tmp['date_update'] = $this->Component->Date->ago($item->getDateUpdate(), true);
      $tmp['size'] = $this->Component->Utility->formatSize($item->getSizebytes());
      $tmp['policy'] = $item->policy;
      $tmp['privacy_status'] = $item->privacy_status;
      $jsonContent[$item->parent_id]['items'][] = $tmp;
      unset($tmp);
      }
    echo JsonComponent::encode($jsonContent);
    }//end getfolderscontent

  /** get getfolders Items' size */
  public function getfolderssizeAction()
    {
    $this->requireAjaxRequest();
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();
    $folderIds = $this->_getParam('folders');
    if(!isset($folderIds))
      {
      echo "[]";
      return;
      }
    $folderIds = explode('-', $folderIds);
    $folders = $this->Folder->load($folderIds);
    $folders = $this->Folder->getSizeFiltered($folders, $this->userSession->Dao);
    $return = array();
    foreach($folders as $folder)
      {
      $return[] = array('id' => $folder->getKey(), 'count' => $folder->count, 'size' => $this->Component->Utility->formatSize($folder->size));
      }
    echo JsonComponent::encode($return);
    }//end getfolderscontent

  /** get element info (ajax function for the treetable) */
  public function getelementinfoAction()
    {
    $this->requireAjaxRequest();
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();
    $element = $this->_getParam('type');
    $id = $this->_getParam('id');
    if(!isset($id) || !isset($element))
      {
      throw new Zend_Exception("Please double check the parameters");
      }
    $jsonContent = array('type' => $element);
    switch($element)
      {
      case 'community':
        $community = $this->Community->load($id);
        $jsonContent = array_merge($jsonContent, $community->toArray());
        $jsonContent['creation'] = $this->Component->Date->formatDate(strtotime($community->getCreation()));
        $members = $community->getMemberGroup()->getUsers();
        $jsonContent['members'] = count($members);
        break;
      case 'folder':
        $folder = $this->Folder->load($id);
        $jsonContent = array_merge($jsonContent, $folder->toArray());
        $jsonContent['creation'] = $this->Component->Date->formatDate(strtotime($jsonContent['date_update']));
        if(!isset($this->userSession->recentFolders))
          {
          $this->userSession->recentFolders = array();
          }
        array_push($this->userSession->recentFolders, $folder->getKey());
        if(count($this->userSession->recentFolders) > 5)
          {
          array_shift($this->userSession->recentFolders);
          }
        break;
      case 'item':
        $item = $this->Item->load($id);
        $jsonContent = array_merge($jsonContent, $item->toArray());
        $itemRevision = $this->Item->getLastRevision($item);
        if(isset($itemRevision) && $itemRevision !== false)
          {
          $jsonContent['creation'] = $this->Component->Date->formatDate(strtotime($itemRevision->getDate()));
          $jsonContent['uploaded'] = $itemRevision->getUser()->toArray();
          $jsonContent['revision'] = $itemRevision->toArray();
          $jsonContent['nbitstream'] = count($itemRevision->getBitstreams());
          }
        else
          {
          $jsonContent['creation'] = $this->Component->Date->formatDate(strtotime($item->getDateCreation()));
          $jsonContent['norevisions'] = true;
          }
        $jsonContent['type'] = 'item';
        break;
      default:
        throw new Zend_Exception("Please select the right type of element.");
        break;
      }
    $jsonContent['translation']['Created'] = $this->t('Created');
    $jsonContent['translation']['File'] = $this->t('File');
    $jsonContent['translation']['Uploaded'] = $this->t('Uploaded by');
    $jsonContent['translation']['Private'] = $this->t('This community is private');
    echo JsonComponent::encode($jsonContent);
    }//end getElementInfo


  /** review (browse) uploaded files*/
  public function uploadedAction()
    {
    if(empty($this->userSession->uploaded) || !$this->logged)
      {
      $this->_redirect('/');
      }
    $this->view->activemenu = 'uploaded'; // set the active menu
    $this->view->items = array();
    $this->view->header = $this->t('Uploaded Files');
    $this->view->Date = $this->Component->Date;
    foreach($this->userSession->uploaded as $item)
      {
      $item = $this->Item->load($item);
      if($item != false)
        {
        $item->policy = MIDAS_POLICY_ADMIN;
        $item->size = $this->Component->Utility->formatSize($item->getSizebytes());
        $this->view->items[] = $item;
        }
      }
    $this->view->json['item']['message']['delete'] = $this->t('Delete');
    $this->view->json['item']['message']['deleteMessage'] = $this->t('Do you really want to delete this item? It cannot be undone.');
    $this->view->json['item']['message']['merge'] = $this->t('Merge Files in one Item');
    $this->view->json['item']['message']['mergeName'] = $this->t('Name of the item');
    }

  /**
   * Delete a set of folders and items. Called by ajax from common.browser.js
   * @param folders A list of folder ids separated by '-'
   * @param items A list of item ids separated by '-'
   * @return Replies with a json object of the form:
             {success: {folders: [<id>, <id>, ...], items: [<id>, <id>, ...]},
              failure: {folders: [<id>, <id>, ...], items: [<id>, <id>, ...]}}
     Denoting which deletes succeeded and which failed.  Invalid ids will be considered
     already deleted and are thus returned as successful.
   */
  public function deleteAction()
    {
    if(!$this->logged)
      {
      throw new Zend_Exception('You must be logged in to delete resources.');
      }

    $this->disableLayout();
    $this->_helper->viewRenderer->setNoRender();

    $folderIds = $this->_getParam('folders');
    $itemIds = $this->_getParam('items');

    $resp = array('success' => array('folders' => array(), 'items' => array()),
                  'failure' => array('folders' => array(), 'items' => array()));
    $folderIds = explode('-', $folderIds);
    $itemIds = explode('-', $itemIds);

    foreach($folderIds as $folderId)
      {
      if($folderId == '')
        {
        continue;
        }
      $folder = $this->Folder->load($folderId);
      if(!$folder)
        {
        $resp['success']['folders'][] = $folderId; //probably deleted by a parent delete
        continue;
        }

      if($this->Folder->policyCheck($folder, $this->userSession->Dao, MIDAS_POLICY_ADMIN) &&
         $this->Folder->isDeleteable($folder))
        {
        $this->Folder->delete($folder);
        $resp['success']['folders'][] = $folderId;
        }
      else
        {
        $resp['failure']['folders'][] = $folderId; //permission failure
        }
      }

    foreach($itemIds as $itemId)
      {
      if($itemId == '')
        {
        continue;
        }
      $item = $this->Item->load($itemId);
      if(!$item)
        {
        $resp['success']['items'][] = $itemId; //probably deleted by a parent delete
        continue;
        }

      if($this->Item->policyCheck($item, $this->userSession->Dao, MIDAS_POLICY_ADMIN))
        {
        $this->Item->delete($item);
        $resp['success']['items'][] = $itemId;
        }
      else
        {
        $resp['failure']['items'][] = $itemId; //permission failure
        }
      }
    echo JsonComponent::encode($resp);
    }
} // end class

