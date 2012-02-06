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
    $this->_models = array('Bitstream', 'Item', 'ItemRevision', 'User');
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

  /** test duplication of an item */
  public function testDuplicate()
    {
    $itemsFile = $this->loadData('Item', 'default');
    $usersFile = $this->loadData('User', 'default');
    $item1 = $this->Item->load($itemsFile[0]->getKey());
    $adminUser = $this->User->load($usersFile[2]->getKey());

    // Make sure the item is duplicated properly
    $oldCount = count($adminUser->getPrivateFolder()->getItems());
    $this->Item->duplicateItem($item1, $adminUser, $adminUser->getPrivateFolder());
    $adminUser = $this->User->load($usersFile[2]->getKey());
    $items = $adminUser->getPrivateFolder()->getItems();
    $this->assertEquals(count($items), $oldCount + 1);

    // Make sure all the information is the same, but the key, date, and uuid are different
    $duplicate = $items[$oldCount];
    $this->assertNotEquals($item1->getKey(), $duplicate->getKey());
    $this->assertNotEquals($item1->getDateCreation(), $duplicate->getDateCreation());
    $this->assertNotEquals($item1->getUuid(), $duplicate->getUuid());
    $this->assertEquals($item1->getName(), $duplicate->getName());
    $this->assertEquals($item1->getDescription(), $duplicate->getDescription());
    $this->assertEquals($item1->getType(), $duplicate->getType());

    // Make sure revisions were copied correctly
    $oldRevs = $item1->getRevisions();
    $newRevs = $duplicate->getRevisions();
    $this->assertTrue(count($oldRevs) > 0);
    $this->assertEquals(count($oldRevs), count($newRevs));

    for($i = 0; $i < count($oldRevs); $i++)
      {
      $this->assertNotEquals($oldRevs[$i]->getKey(), $newRevs[$i]->getKey());
      $this->assertNotEquals($oldRevs[$i]->getUuid(), $newRevs[$i]->getUuid());
      $this->assertEquals($oldRevs[$i]->getDate(), $newRevs[$i]->getDate());
      $this->assertEquals($oldRevs[$i]->getRevision(), $newRevs[$i]->getRevision());
      $this->assertEquals($oldRevs[$i]->getLicenseId(), $newRevs[$i]->getLicenseId());
      $this->assertEquals($oldRevs[$i]->getChanges(), $newRevs[$i]->getChanges());
      $this->assertEquals($newRevs[$i]->getUserId(), $adminUser->getKey());

      // Make sure the bitstream records are duplicated
      $oldBitstreams = $oldRevs[$i]->getBitstreams();
      $newBitstreams = $newRevs[$i]->getBitstreams();
      $this->assertEquals(count($oldBitstreams), count($newBitstreams));
      for($b = 0; $b < count($oldBitstreams); $b++)
        {
        $this->assertNotEquals($oldBitstreams[$b]->getKey(), $newBitstreams[$b]->getKey());
        $this->assertNotEquals($oldBitstreams[$b]->getItemrevisionId(), $newBitstreams[$b]->getItemrevisionId());
        $this->assertEquals($oldBitstreams[$b]->getDate(), $newBitstreams[$b]->getDate());
        $this->assertEquals($oldBitstreams[$b]->getMimetype(), $newBitstreams[$b]->getMimetype());
        $this->assertEquals($oldBitstreams[$b]->getSizebytes(), $newBitstreams[$b]->getSizebytes());
        $this->assertEquals($oldBitstreams[$b]->getChecksum(), $newBitstreams[$b]->getChecksum());
        $this->assertEquals($oldBitstreams[$b]->getPath(), $newBitstreams[$b]->getPath());
        }
      }
    }
  }
