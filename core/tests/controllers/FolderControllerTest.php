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
/** test folder controller*/
class FolderControllerTest extends ControllerTestCase
  {
  /** init test*/
  public function setUp()
    {
    $this->setupDatabase(array('default'));
    $this->_models = array('Folder', 'Item', 'User');
    parent::setUp();
    }

  /** Test view action */
  public function testViewAction()
    {
    $foldersFile = $this->loadData('Folder', 'default');
    $usersFile = $this->loadData('User', 'default');
    $userWithPermission = $this->User->load($usersFile[2]->getKey());
    $folder = $this->Folder->load($foldersFile[1]->getKey());

    $this->dispatchUri('/folder/view', null, true);

    $this->resetAll();
    $this->dispatchUri('/folder/'.$folder->getKey(), null, true);

    $this->resetAll();
    $this->dispatchUri('/folder/'.$folder->getKey(), $userWithPermission);
    $this->assertController('folder');
    $this->assertAction('view');

    // Create a new folder under a private folder
    $this->resetAll();
    $this->getRequest()->setMethod('POST');
    $this->dispatchUri('/folder/createfolder?folderId=1001&createFolder&name=Sub', $userWithPermission);

    $folder = $this->Folder->load(1001);
    $children = $folder->getFolders();
    $this->assertEquals(count($children), 1);

    $this->resetAll();
    $this->dispatchUri('/folder/1001');
    $this->resetAll();
    $this->dispatchUri('/folder/'.$children[0]->getKey());
    }

  /** Test edit action */
  public function testEditAction()
    {
    $foldersFile = $this->loadData('Folder', 'default');
    $usersFile = $this->loadData('User', 'default');
    $userWithPermission = $this->User->load($usersFile[2]->getKey());
    $folder = $this->Folder->load($foldersFile[4]->getKey());

    $this->dispatchUri('/folder/edit', null, true);

    $this->resetAll();
    $this->dispatchUri('/folder/edit?folderId='.$folder->getKey(), null, true);

    // Render the edit view
    $this->resetAll();
    $this->dispatchUri('/folder/edit?folderId='.$folder->getKey(), $userWithPermission);
    $this->assertController('folder');
    $this->assertAction('edit');

    // We should not be able to change name to a sibling's name
    $this->resetAll();
    $this->getRequest()->setMethod('POST');
    $this->params = array();
    $this->params['name'] = 'User 1 name Folder 3';
    $this->params['description'] = '';
    $this->params['teaser'] = '';
    $this->dispatchUri('/folder/edit?folderId='.$folder->getKey(), $userWithPermission, true);

    $folder = $this->Folder->load($foldersFile[4]->getKey());
    $this->assertEquals($folder->getName(), 'User 1 name Folder 2');

    // Test changing the folder information
    $this->resetAll();
    $this->getRequest()->setMethod('POST');
    $this->params = array();
    $this->params['name'] = 'new name';
    $this->params['description'] = 'new description';
    $this->params['teaser'] = 'new teaser';
    $this->dispatchUri('/folder/edit?folderId='.$folder->getKey(), $userWithPermission);
    $this->assertController('folder');
    $this->assertAction('edit');

    $folder = $this->Folder->load($foldersFile[4]->getKey());
    $this->assertEquals($folder->getName(), 'new name');
    $this->assertEquals($folder->getDescription(), 'new description');
    $this->assertEquals($folder->getTeaser(), 'new teaser');
    }

  /** Test delete action */
  public function testDeleteAction()
    {
    $usersFile = $this->loadData('User', 'default');
    $userWithPermission = $this->User->load($usersFile[2]->getKey());

    // Must pass a folder id parameter
    $this->dispatchUri('/folder/delete', null, true);

    // Anonymous user should not be able to delete a folder
    $this->resetAll();
    $this->dispatchUri('/folder/delete?folderId=2', null, true);

    // We should not be able to delete a user root folder
    $this->resetAll();
    $this->dispatchUri('/folder/delete?folderId=1000', $userWithPermission, true);

    // We should not be able to delete a user private folder
    $this->resetAll();
    $this->dispatchUri('/folder/delete?folderId=1001', $userWithPermission, true);

    // We should not be able to delete a user public folder
    $this->resetAll();
    $this->dispatchUri('/folder/delete?folderId=1002', $userWithPermission, true);

    // We should not be able to delete a community root folder
    $this->resetAll();
    $this->dispatchUri('/folder/delete?folderId=1003', $userWithPermission, true);

    // We should not be able to delete a community private folder
    $this->resetAll();
    $this->dispatchUri('/folder/delete?folderId=1004', $userWithPermission, true);

    // We should not be able to delete a community public folder
    $this->resetAll();
    $this->dispatchUri('/folder/delete?folderId=1005', $userWithPermission, true);

    // Create a new folder under a private folder
    $this->resetAll();
    $this->getRequest()->setMethod('POST');
    $this->dispatchUri('/folder/createfolder?folderId=1001&createFolder&name=Sub', $userWithPermission);

    $folder = $this->Folder->load(1001);
    $children = $folder->getFolders();
    $this->assertEquals(count($children), 1);

    // Delete the child folder should succeed
    $this->resetAll();
    $this->dispatchUri('/folder/delete?folderId='.$children[0]->getKey(), $userWithPermission);
    $folder = $this->Folder->load(1001);
    $children = $folder->getFolders();
    $this->assertEquals(count($children), 0);
    }

  /** Test the view for createfolder */
  public function testCreateFolderView()
    {
    $usersFile = $this->loadData('User', 'default');
    $userWithPermission = $this->User->load($usersFile[2]->getKey());
    $this->dispatchUri('/folder/createfolder?folderId=1001', $userWithPermission);
    $this->assertController('folder');
    $this->assertAction('createfolder');
    $this->assertQuery('form#createFolderForm');
    $this->assertQuery('input[name="folderId"][value="1001"]');
    $this->assertQuery('input[type="submit"][name="createFolder"]');
    $this->assertQueryContentContains('label', 'Name');
    $this->assertQueryContentContains('label', 'Description');
    $this->assertQueryContentContains('label', 'Teaser');
    }

  /** Test removeitem action */
  public function testRemoveItemAction()
    {
    $usersFile = $this->loadData('User', 'default');
    $userWithPermission = $this->User->load($usersFile[2]->getKey());

    // Anonymous user should not be able to remove an item
    $this->dispatchUri('/folder/removeitem?itemId=1&folderId=1001', null, true);

    // Should get an exception for no item id/no folder id
    $this->resetAll();
    $this->dispatchUri('/folder/removeitem', $userWithPermission, true);

    // Should get an exception for invalid item/folder id
    $this->resetAll();
    $this->dispatchUri('/folder/removeitem?itemId=2190381&folderId=91230', $userWithPermission, true);

    // We should be able to remove an item from a folder
    $folder = $this->Folder->load(1001);
    $items = $folder->getItems();
    $count = count($items);
    $this->assertTrue($count >= 1);

    $this->resetAll();
    $this->dispatchUri('/folder/removeitem?itemId=1&folderId=1001', $userWithPermission);
    $folder = $this->Folder->load(1001);
    $items = $folder->getItems();
    $this->assertTrue(count($items) === $count - 1);
    }
  }
