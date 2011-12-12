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
