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
/** ItemModelTest*/
class ItemModelTest extends DatabaseTestCase
  {
  /** init tests*/
  public function setUp()
    {
    $this->setupDatabase(array());
    $this->_models = array(
      'Item', 'ItemRevision'
    );
    $this->_daos = array();
    parent::setUp();
    }

  /** testGetLastRevision */
  public function testGetLastRevision()
    {
    $itemsFile = $this->loadData('Item', 'default');
    $revisionsFile = $this->loadData('ItemRevision', 'default');
    $revision = $this->Item->getLastRevision($itemsFile[0]);
    $revisionKey = $revision->getKey();
    $revisionRevision = $revision->getRevision();
    // get the id for this item
    $itemId = $itemsFile[0]->getKey();
    // loop through all revisions, find highest that matches item id
    $found = false;
    foreach($revisionsFile as $revisionDao)
      {
      if($revisionDao->getItemId() === $itemId)
        {
        // see if we find the matching highest
        if($revisionDao->getKey() === $revisionKey)
          {
          $found = true;
          }
        if($revisionDao->getRevision() > $revisionRevision)
          {
          $this->fail("testGetLastRevision found a revision higher than that of getLastRevision()");
          }
        }
      }
    $this->assertTrue($found, "testGetLastRevision never found the highest revision with getLastRevision()");
    }

  /** testAddRevision*/
  public function testAddRevision()
    {
    $itemsFile = $this->loadData('Item', 'default');
    $usersFile = $this->loadData('User', 'default');
    $revision = new ItemRevisionDao();
    $revision->setUserId($usersFile[0]->getKey());
    $revision->setDate(date('c'));
    $revision->setChanges("change");
    $revision->setItemId(0);
    $revision->setRevision(1);
    $this->ItemRevision->save($revision);

    $this->Item->addRevision($itemsFile[1], $revision);
    $revisionTmp = $this->Item->getLastRevision($itemsFile[1]);
    $this->assertEquals($revision->getKey(), $revisionTmp->getKey());
    }

  }
