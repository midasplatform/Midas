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
    $this->_models = array('User', 'Folder', 'Item', 'Folderpolicyuser', 'Itempolicyuser');
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

    // Assert that we cannot batch delete our private or public folder
    $this->resetAll();
    $this->params['items'] = '';
    $this->params['folders'] = $userDao->getPrivatefolderId().'-'.$userDao->getPublicfolderId().'-';
    $this->dispatchUrI('/browse/delete', $userDao);
    $this->assertNotEmpty($this->getBody());
    $resp = json_decode($this->getBody());
    $this->assertNotEmpty($resp->success);
    $this->assertNotEmpty($resp->failure);
    $this->assertEmpty($resp->success->folders);
    $this->assertEmpty($resp->success->items);
    $this->assertEquals(count($resp->failure->folders), 2);
    $this->assertEmpty($resp->failure->items);
    $this->assertEquals($resp->failure->folders[0], $userDao->getPrivatefolderId());
    $this->assertEquals($resp->failure->folders[1], $userDao->getPublicfolderId());

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
    $folder = $this->Folder->createFolder('DeleteableFolder', 'Description', $userDao->getPublicFolder());
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
   * Test the following functionalities in movecopy controller in the browse view
   * 1) Share an item
   * 2) Duplicate an item
   */
  public function testMoveCopyAction()
    {

    }
  }
