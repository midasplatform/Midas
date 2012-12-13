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
/** Test for the Browse controller*/
class BrowseControllerTest extends ControllerTestCase
  {
  /** init tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default'));
    $this->_models = array('Community', 'User', 'Folder', 'Item', 'Folderpolicyuser', 'Itempolicyuser');
    $this->_daos = array();
    parent::setUp();
    }

  /**
   * Test the batch delete controller exposed as "delete all selected"
   * action in the browse views
   */
  public function testBatchDeleteAction()
    {
    // Anonymous access should throw an exception
    $this->dispatchUrI('/browse/delete', null, true);

    $usersFile = $this->loadData('User', 'default');
    $foldersFile = $this->loadData('Folder', 'default');
    $itemsFile = $this->loadData('Item', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());

    // Assert that we can handle empty id values
    $this->resetAll();
    $this->params['items'] = '-';
    $this->params['folders'] = '-';
    $this->dispatchUrI('/browse/delete', $userDao);
    $this->assertNotEmpty($this->getBody());
    $resp = json_decode($this->getBody());
    $this->assertNotEmpty($resp->success);
    $this->assertNotEmpty($resp->failure);
    $this->assertEmpty($resp->success->folders);
    $this->assertEmpty($resp->success->items);
    $this->assertEmpty($resp->failure->folders);
    $this->assertEmpty($resp->failure->items);

    // Assert that deleting a nonexistent item and folder returns success
    $this->resetAll();
    $this->params['items'] = '999875-';
    $this->params['folders'] = '787134-';
    $this->dispatchUrI('/browse/delete', $userDao);
    $this->assertNotEmpty($this->getBody());
    $resp = json_decode($this->getBody());
    $this->assertNotEmpty($resp->success);
    $this->assertNotEmpty($resp->failure);
    $this->assertEquals(count($resp->success->folders), 1);
    $this->assertEquals(count($resp->success->items), 1);
    $this->assertEmpty($resp->failure->folders);
    $this->assertEmpty($resp->failure->items);
    $this->assertEquals($resp->success->folders[0], '787134');
    $this->assertEquals($resp->success->items[0], '999875');

    // Assert that we can delete an item and folder we have permissions on
    $item = $this->Item->load($itemsFile[1]->getKey());
    $itemId = $item->getKey();
    $this->Itempolicyuser->createPolicy($userDao, $item, MIDAS_POLICY_ADMIN);
    $folder = $this->Folder->createFolder('DeleteableFolder', 'Description', $userDao->getFolder());
    $folderId = $folder->getKey();
    $this->Folderpolicyuser->createPolicy($userDao, $folder, MIDAS_POLICY_ADMIN);

    $this->assertNotEquals($item, false);
    $this->resetAll();
    $this->params['items'] = $item->getKey().'-';
    $this->params['folders'] = $folder->getKey().'-';
    $this->dispatchUrI('/browse/delete', $userDao);
    $item = $this->Item->load($item->getKey());
    $folder = $this->Folder->load($folder->getKey());
    $this->assertEquals($item, false);
    $this->assertEquals($folder, false);
    $this->assertNotEmpty($this->getBody());
    $resp = json_decode($this->getBody());
    $this->assertNotEmpty($resp->success);
    $this->assertNotEmpty($resp->failure);
    $this->assertEquals(count($resp->success->folders), 1);
    $this->assertEquals(count($resp->success->items), 1);
    $this->assertEmpty($resp->failure->folders);
    $this->assertEmpty($resp->failure->items);
    $this->assertEquals($resp->success->folders[0], $folderId);
    $this->assertEquals($resp->success->items[0], $itemId);
    }

  /**
   * Renders the "explore" page
   */
  public function testIndexAction()
    {
    $this->dispatchUrI('/browse/index', null);
    }

  /**
   * Test the movecopy view and action
   */
  public function testMovecopyAction()
    {
    $usersFile = $this->loadData('User', 'default');
    $commFile = $this->loadData('Community', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());

    // First we go through the dialog rendering code
    $this->dispatchUrI('/browse/movecopy?share=true&duplicate=true&move=true&items=1001-', $userDao);
    $this->assertQueryCount('#moveCopyTable tr[type="community"]', 2);
    $this->assertQueryContentContains('#moveCopyTable tr[type="community"]', $commFile[0]->getName());
    $this->assertQueryContentContains('#moveCopyTable tr[type="community"]', $commFile[1]->getName());
    $this->assertQueryCount('#moveCopyTable tr[type="folder"]', 1);
    $this->assertQueryContentContains('#moveCopyTable tr[type="folder"]', 'My Files ('.$userDao->getFullname().')');

    // Attempting to move item without permission should throw exception
    $this->resetAll();
    $this->dispatchUri('/browse/movecopy?destination=1002&moveElement=Move&elements=;1001-&from=1001', null, true);

    // Test move of an item
    $oldFolder = $this->Folder->load(1001);
    $oldCountSrc = count($oldFolder->getItems());
    $newFolder = $this->Folder->load(1002);
    $oldCountDst = count($newFolder->getItems());
    $this->resetAll();
    $this->dispatchUri('/browse/movecopy?destination=1002&moveElement=Move&elements=;1001-&from=1001', $userDao);
    $oldFolder = $this->Folder->load(1001);
    $newFolder = $this->Folder->load(1002);
    $this->assertEquals(count($oldFolder->getItems()), $oldCountSrc - 1); //assert item removed from old folder
    $this->assertEquals(count($newFolder->getItems()), $oldCountDst + 1); //assert item added to new folder

    // Test duplicate of an item
    $this->setupDatabase(array('default')); //reset database
    $oldFolder = $this->Folder->load(1001);
    $oldCountSrc = count($oldFolder->getItems());
    $newFolder = $this->Folder->load(1002);
    $oldCountDst = count($newFolder->getItems());
    $this->resetAll();
    $this->dispatchUri('/browse/movecopy?destination=1002&copytype=copy&duplicateElement=Duplicate&elements=;1001-', $userDao);
    $oldFolder = $this->Folder->load(1001);
    $newFolder = $this->Folder->load(1002);
    $this->assertEquals(count($oldFolder->getItems()), $oldCountSrc); //assert item stayed in old folder
    $this->assertEquals(count($newFolder->getItems()), $oldCountDst + 1); //assert item added to new folder

    // Test share of an item
    $this->setupDatabase(array('default')); //reset database
    $oldFolder = $this->Folder->load(1001);
    $oldCountSrc = count($oldFolder->getItems());
    $newFolder = $this->Folder->load(1002);
    $oldCountDst = count($newFolder->getItems());
    $item = $this->Item->load(1001);
    $oldCountParents = count($item->getFolders());
    $this->resetAll();
    $this->dispatchUri('/browse/movecopy?destination=1002&copytype=reference&shareElement=Share&elements=;1001-', $userDao);
    $oldFolder = $this->Folder->load(1001);
    $newFolder = $this->Folder->load(1002);
    $item = $this->Item->load(1001);
    $this->assertEquals(count($oldFolder->getItems()), $oldCountSrc); //assert item stayed in old folder
    $this->assertEquals(count($newFolder->getItems()), $oldCountDst + 1); //assert item added to new folder
    $this->assertEquals(count($item->getFolders()), $oldCountParents + 1); //assert item now has an additional parent

    // Test move of a folder
    $this->setupDatabase(array('default')); //reset database
    $folder = $this->Folder->load(1002);
    $this->assertTrue($folder->getParent()->getKey() == $userDao->getFolderId());
    $this->resetAll();
    $this->dispatchUri('/browse/movecopy?destination=1001&moveElement=Move&elements=1002-;', $userDao);
    $folder = $this->Folder->load(1002);
    $this->assertFalse($folder->getParent()->getKey() == $userDao->getFolderId());
    $this->assertTrue($folder->getParent()->getKey() == 1001);
    }

  /**
   * Test selectitem and selectfolder actions
   */
  public function testSelectDialogs()
    {
    $usersFile = $this->loadData('User', 'default');
    $commFile = $this->loadData('Community', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());

    $this->dispatchUri('/browse/selectitem', $userDao);
    $this->assertQueryContentContains('tr[type="community"]', $commFile[0]->getName());
    $this->assertQueryContentContains('tr[type="community"]', $commFile[1]->getName());
    $this->assertQueryContentContains('tr[type="folder"]', 'My Files ('.$userDao->getFullname().')');

    $this->resetAll();
    $this->dispatchUri('/browse/selectfolder?policy=read', $userDao); //test with read policy
    $this->assertQueryCount('tr[type="community"]', 2);
    $this->assertQueryContentContains('tr[type="community"]', $commFile[0]->getName());
    $this->assertQueryContentContains('tr[type="community"]', $commFile[1]->getName());
    $this->assertQueryCount('tr[type="folder"]', 1);
    $this->assertQueryContentContains('tr[type="folder"]', 'My Files ('.$userDao->getFullname().')');

    $this->resetAll();
    $this->dispatchUri('/browse/selectfolder', $userDao); //test with write policy
    $this->assertQueryCount('tr[type="community"]', 1);
    $this->assertQueryContentContains('tr[type="community"]', $commFile[0]->getName());
    $this->assertQueryCount('tr[type="folder"]', 1);
    $this->assertQueryContentContains('tr[type="folder"]', 'My Files ('.$userDao->getFullname().')');
    }

  /**
   * Test the ajax action for listing folder children
   */
  public function testGetfolderscontentAction()
    {
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());

    $folder1000 = $this->Folder->load(1000);
    $folder1001 = $this->Folder->load(1001);
    $this->dispatchUri('/browse/getfolderscontent?folders=1000-1001-', $userDao);
    $resp = json_decode($this->getBody(), true);
    $this->assertTrue($resp['1000'] != null);
    $this->assertTrue($resp['1001'] != null);
    $this->assertEquals(count($resp['1000']['folders']), count($folder1000->getFolders()));
    $this->assertEquals(count($resp['1001']['folders']), count($folder1001->getFolders()));
    $this->assertEquals(count($resp['1000']['items']), count($folder1000->getItems()));
    $this->assertEquals(count($resp['1001']['items']), count($folder1001->getItems()));
    }

  /**
   * Test retrieving a folder's size
   */
  public function testGetfolderssizeAction()
    {
    Zend_Registry::set('notifier', new MIDAS_Notifier(false, null));
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());

    $folder1001 = $this->Folder->load(1001);
    $folder1002 = $this->Folder->load(1002);
    $item = $this->Item->load(1001);
    $item->setSizebytes(1024000); //artificially set the item size for test purposes
    $this->Item->save($item);
    $this->dispatchUri('/browse/getfolderssize?folders=1001-1002-', $userDao);
    $resp = json_decode($this->getBody(), true);
    $this->assertEquals(count($resp), 2); //should have results for two folders
    $this->assertEquals($resp[0]['id'], 1001);
    $this->assertEquals($resp[0]['count'], 3);
    $this->assertEquals($resp[0]['size'], '1.0 MB');
    $this->assertEquals($resp[1]['id'], 1002);
    $this->assertEquals($resp[1]['count'], 0);
    $this->assertEquals($resp[1]['size'], '0.0 KB');
    }

  /**
   * Test retrieval of item and folder information
   */
  public function testGetelementinfoAction()
    {
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());

    // test get folder info
    $folder = $this->Folder->load(1001);
    $this->dispatchUri('/browse/getelementinfo?type=folder&id=1001', $userDao);
    $resp = json_decode($this->getBody(), true);
    $this->assertEquals($resp['type'], 'folder');
    $this->assertEquals($resp['folder_id'], 1001);
    $this->assertEquals($resp['parent_id'], 1000);
    $this->assertEquals($resp['name'], $folder->getName());
    $this->assertEquals($resp['description'], $folder->getDescription());

    // test get item info
    $item = $this->Item->load(1001);
    $this->resetAll();
    $this->dispatchUri('/browse/getelementinfo?type=item&id=1001', $userDao);
    $resp = json_decode($this->getBody(), true);
    $this->assertEquals($resp['type'], 'item');
    $this->assertEquals($resp['item_id'], 1001);
    $this->assertEquals($resp['name'], $item->getName());
    $this->assertEquals($resp['description'], $item->getDescription());
    $this->assertEquals($resp['creation'], '01/27/2011');

    // test get community info
    $comm = $this->Community->load(2000);
    $this->resetAll();
    $this->dispatchUri('/browse/getelementinfo?type=community&id=2000', $userDao);
    $resp = json_decode($this->getBody(), true);
    $this->assertEquals($resp['type'], 'community');
    $this->assertEquals($resp['community_id'], 2000);
    $this->assertEquals($resp['name'], $comm->getName());
    $this->assertEquals($resp['description'], $comm->getDescription());
    $this->assertEquals($resp['creation'], '01/27/2011');
    }
  }
