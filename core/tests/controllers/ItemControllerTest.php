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
/** test item controller*/
class ItemControllerTest extends ControllerTestCase
  {

  /** init test*/
  public function setUp()
    {
    $this->setupDatabase(array('default'));
    $this->_models = array('Item', 'ItemRevision', 'User');
    parent::setUp();
    }

  /** Test explicit deletion of an item */
  public function testDeleteAction()
    {
    $itemsFile = $this->loadData('Item', 'default');
    $usersFile = $this->loadData('User', 'default');
    $userWithPermission = $this->User->load($usersFile[0]->getKey());
    $userWithoutPermission = $this->User->load($usersFile[1]->getKey());

    $itemDao = $this->Item->load($itemsFile[1]->getKey());
    $this->assertEquals(count($itemDao->getRevisions()), 1);
    $revisions = $itemDao->getRevisions();
    $revisionId = $revisions[0]->getItemrevisionId();

    $url = '/item/delete?itemId='.$itemDao->getItemId();

    // Should throw an exception for anonymous user
    $this->dispatchUrI($url, null, true);
    $this->assertController('error');

    // Should throw an exception for normal user
    $this->resetAll();
    $this->dispatchUrI($url, $userWithoutPermission, true);
    $this->assertController('error');

    // User with proper privileges should be able to delete the item
    $this->resetAll();
    $this->dispatchUrI($url, $userWithPermission);
    $this->assertController('item');
    $this->assertAction('delete');

    $this->assertFalse($this->Item->load($itemsFile[1]->getKey()));
    $this->assertFalse($this->ItemRevision->load($revisionId));
    }

  }
