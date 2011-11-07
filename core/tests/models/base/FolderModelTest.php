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
  }
