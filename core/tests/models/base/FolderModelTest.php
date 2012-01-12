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
/** FolderModelTest*/
class FolderModelTest extends DatabaseTestCase
  {
  /** init test*/
  public function setUp()
    {
    $this->setupDatabase(array('default'));
    $this->_models = array(
      'Folder', "Community", "Item", "Folderpolicyuser"
    );
    $this->_daos = array(
      'Folder'
    );
    Zend_Registry::set('modulesEnable', array());
    Zend_Registry::set('notifier', new MIDAS_Notifier(false, null));
    parent::setUp();
    }

  /** testCreateSaveAndDelete*/
  public function testCreateSaveAndDelete()
    {
    $folder = $this->Folder->createFolder("TestNameFolder", "Description", 0);
    $this->assertEquals(true, $folder->saved);
    $id = $folder->getKey();
    $folder->setName("NewName");
    $this->Folder->save($folder);
    $folderTmp = $this->Folder->load($id);
    $this->assertEquals("NewName", $folderTmp->getName());
    $this->Folder->delete($folder);
    $folder = $this->Folder->load($id);
    $this->assertEquals(false, $folder);
    }

  /** testGetCommunity*/
  public function testGetCommunity()
    {
    $communitiesFile = $this->loadData('Community', 'default');
    $folder = $communitiesFile[0]->getFolder();
    $community = $this->Folder->getCommunity($folder);
    if(!$this->Community->compareDao($community, $communitiesFile[0]))
      {
      $this->fail("Unable to get community");
      }
    }

  /** testGetItemsFiltered*/
  public function testGetItemsFiltered()
    {
    $usersFile = $this->loadData('User', 'default');
    $folder = $usersFile[0]->getPublicFolder();
    $items = $folder->getItems();
    $itemsFiltered = $this->Folder->getItemsFiltered($folder, $usersFile[0], 0);
    $this->assertEquals(2, count($itemsFiltered));
    if(!isset($itemsFiltered[0]) || !isset($items[0]) || $itemsFiltered[0]->getKey() != $items[0]->getKey())
      {
      $this->fail("Unable to get community");
      }
    }

  /** testGetChildrenFoldersFiltered*/
  public function testGetChildrenFoldersFiltered()
    {
    $usersFile = $this->loadData('User', 'default');
    $folder = $usersFile[0]->getFolder();
    $folders = $this->Folder->getChildrenFoldersFiltered($folder, $usersFile[0], 0);
    $this->assertEquals(2, count($folders));
    }

  /** testGetFolderByName*/
  public function testGetFolderByName()
    {
    $usersFile = $this->loadData('User', 'default');
    $folder = $usersFile[0]->getFolder();
    $childs = $folder->getFolders();
    $child = $this->Folder->getFolderByName($folder, $childs[0]->getName());
    $this->assertEquals($childs[0]->getName(), $child->getName());
    }

  /** testGetItemByName*/
  public function testGetItemByName()
    {
    $itemsFile = $this->loadData('Item', 'default');
    $folder = $itemsFile[0]->getFolders();
    $folder = $folder[0];
    $item = $this->Folder->getItemByName($folder, $itemsFile[0]->getName());
    $this->assertEquals($itemsFile[0]->getName(), $item->getName());
    }

  /** testGetFoldersFromSearch*/
  public function testGetFoldersFromSearch()
    {
    $usersFile = $this->loadData('User', 'default');
    $results = $this->Folder->getFoldersFromSearch("aaaaaaaaaaZeroResults", $usersFile[0]);
    $this->assertEquals(0, count($results));
    $folder = $this->Folder->createFolder("NameSearch", "Description", $usersFile[0]->getPublicFolder());
    $this->Folderpolicyuser->createPolicy($usersFile[0], $folder, MIDAS_POLICY_ADMIN);
    $results = $this->Folder->getFoldersFromSearch("NameSearch", $usersFile[0]);
    $this->assertEquals(1, count($results));
    }

  /** testAddItem*/
  public function testAddItem()
    {
    $usersFile = $this->loadData('User', 'default');
    $item = new ItemDao();
    $item->setName('test');
    $item->setDescription('test');
    $item->setType(0);
    $this->Item->save($item);
    $folder = $usersFile[0]->getFolder();
    $this->Folder->addItem($folder, $item);
    $items = $folder->getItems();
    $this->assertEquals(1, count($items));
    }

  /**
   * Make sure deleting a folder deletes its child items when they are
   * unique to that folder.
   */
  public function testDeleteFolderDeletesItem()
    {
    $folder = $this->Folder->createFolder('TestFolder1', 'Description', 0);
    $this->assertTrue($folder->saved);

    $item = new ItemDao();
    $item->setName('subItem');
    $item->setDescription('test item to be deleted');
    $item->setType(0);
    $this->Item->save($item);
    $this->assertTrue($item->saved);
    $this->Folder->addItem($folder, $item);
    $items = $folder->getItems();
    $this->assertEquals(1, count($items));
    $this->assertEquals($items[0]->getName(), 'subItem');
    $folderid = $folder->getKey();
    $itemid = $items[0]->getKey();

    // Make sure item gets deleted when we delete its parent folder
    $this->Folder->delete($folder, true);
    $folder = $this->Folder->load($folderid);
    $this->assertEquals(false, $folder);
    $item = $this->Item->load($itemid);
    $this->assertEquals(false, $item);
    }

  /**
   * Make sure deleting a folder does not delete its child item if that
   * item is contained in another folder.
   */
  public function testDeleteFolderDoesNotDeleteSharedItem()
    {
    $folder1 = $this->Folder->createFolder('TestFolder1', 'Description', 0);
    $folder2 = $this->Folder->createFolder('TestFolder2', 'Description', 0);
    $this->assertTrue($folder1->saved);
    $this->assertTrue($folder2->saved);

    $item = new ItemDao();
    $item->setName('subItemShared');
    $item->setDescription('test item');
    $item->setType(0);
    $this->Item->save($item);
    $this->assertTrue($item->saved);
    $this->Folder->addItem($folder1, $item);
    $this->Folder->addItem($folder2, $item);
    $items1 = $folder1->getItems();
    $this->assertEquals(1, count($items1));
    $this->assertEquals($items1[0]->getName(), 'subItemShared');
    $items2 = $folder2->getItems();
    $this->assertEquals(1, count($items2));
    $this->assertEquals($items2[0]->getName(), 'subItemShared');
    $folderid1 = $folder1->getKey();
    $folderid2 = $folder2->getKey();
    $itemid = $items2[0]->getKey();

    // Make sure item was not deleted
    $this->Folder->delete($folder1, true);
    $folder = $this->Folder->load($folderid1);
    $this->assertEquals(false, $folder);
    $item = $this->Item->load($itemid);
    $this->assertNotEquals(false, $item, 'Item should not have been deleted');

    // Make sure item record still exists in 2nd folder
    $folder2 = $this->Folder->load($folderid2);
    $items = $folder2->getItems();
    $this->assertEquals(1, count($items), 'Item record was deleted from second folder');
    $this->assertEquals($items[0]->getName(), 'subItemShared');

    // Delete second folder, make sure item is then deleted
    $this->Folder->delete($folder2, true);
    $item = $this->Item->load($itemid);
    $this->assertEquals(false, $item, 'Item should have been deleted');
    }

  /**
   * Test that calling removeItem from a folder deletes a non-shared item
   */
  public function testRemoveItemDeletesNonSharedItem()
    {
    $folder = $this->Folder->createFolder('TestFolder1', 'Description', 0);
    $this->assertTrue($folder->saved);

    $item = new ItemDao();
    $item->setName('subItem');
    $item->setDescription('test item to be deleted');
    $item->setType(0);
    $this->Item->save($item);
    $this->assertTrue($item->saved);
    $this->Folder->addItem($folder, $item);
    $items = $folder->getItems();
    $this->assertEquals(1, count($items));
    $this->assertEquals($items[0]->getName(), 'subItem');
    $folderid = $folder->getKey();
    $itemid = $items[0]->getKey();

    // Make sure item is deleted when it is removed from its only parent
    $this->Folder->removeItem($folder, $item);
    $item = $this->Item->load($itemid);
    $this->assertEquals(false, $item, 'Item should have been deleted');
    }

  /**
   * Test if the Folder->isDeleteable function()
   */
  public function testFolderIsDeleteable()
    {
    $communitiesFile = $this->loadData('Community', 'default');
    $usersFile = $this->loadData('User', 'default');

    // Base, public, and private folders for user and community shouldn't be deleteable
    $this->assertFalse($this->Folder->isDeleteable($communitiesFile[0]->getFolder()));
    $this->assertFalse($this->Folder->isDeleteable($communitiesFile[0]->getPublicFolder()));
    $this->assertFalse($this->Folder->isDeleteable($communitiesFile[0]->getPrivateFolder()));
    $this->assertFalse($this->Folder->isDeleteable($usersFile[0]->getFolder()));
    $this->assertFalse($this->Folder->isDeleteable($usersFile[0]->getPublicFolder()));
    $this->assertFalse($this->Folder->isDeleteable($usersFile[0]->getPrivateFolder()));

    // Make a new top level community folder and make sure it is deleteable
    $folder = $this->Folder->createFolder('TestFolderDeleteable', 'Description', $communitiesFile[0]->getFolder());
    $this->assertTrue($this->Folder->isDeleteable($folder));

    // Make a new folder within the community private folder and make sure it is deleteable
    $folder = $this->Folder->createFolder('TestFolderDeleteable', 'Description', $communitiesFile[0]->getPrivateFolder());
    $this->assertTrue($this->Folder->isDeleteable($folder));

    // Make a new top level user folder and make sure it is deleteable
    $folder = $this->Folder->createFolder('TestFolderDeleteable', 'Description', $usersFile[0]->getFolder());
    $this->assertTrue($this->Folder->isDeleteable($folder));

    // Make a new folder within the user's private folder and make sure it is deleteable
    $folder = $this->Folder->createFolder('TestFolderDeleteable', 'Description', $usersFile[0]->getPrivateFolder());
    $this->assertTrue($this->Folder->isDeleteable($folder));
    }
  }
