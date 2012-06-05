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
    $this->_daos = array('Item');
    parent::setUp();
    }

  /** Test creating an item with a "normal" name as well as the special case
   *  of '0'.
   **/
  public function testCreateItem()
    {
    $usersFile = $this->loadData('User', 'default');
    $adminUser = $this->User->load($usersFile[2]->getKey());

    // Create normal item
    $folderDao = $adminUser->getPrivateFolder();
    $name = 'test name';
    $description = 'test test test';
    $newItem = $this->Item->createItem($name, $description, $folderDao);
    $this->assertEquals($newItem->getName(), $name);
    $this->assertEquals($newItem->getDescription(), $description);

    // Create item with name of '0'
    $name = '0';
    $newItem = $this->Item->createItem($name, $description, $folderDao);
    $this->assertEquals($newItem->getName(), $name);
    $this->assertEquals($newItem->getDescription(), $description);
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
      if((int)$revisionDao->getItemId() === (int)$itemId)
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

  /** testRemoveRevision*/
  public function testRemoveRevision()
    {
    $itemsFile = $this->loadData('Item', 'default');
    $usersFile = $this->loadData('User', 'default');
    $item = $itemsFile[1];

    // remove any revisions that may exist
    while($this->Item->getLastRevision($itemsFile[1]))
      {
      $revision = $this->Item->getLastRevision($itemsFile[1]);
      $this->Item->removeRevision($item, $revision);
      }

    // add on 3 revisions
    $revision = new ItemRevisionDao();
    $revision->setUserId($usersFile[0]->getKey());
    $revision->setDate(date('c'));
    $revision->setChanges("r1");
    $revision->setItemId(0);
    $revision->setRevision(1);
    $this->ItemRevision->save($revision);

    $this->Item->addRevision($itemsFile[1], $revision);
    $lastRev = $this->Item->getLastRevision($item);
    $this->assertEquals($revision->getKey(), $lastRev->getKey());
    $this->assertEquals($revision->getChanges(), $lastRev->getChanges());

    $revision = new ItemRevisionDao();
    $revision->setUserId($usersFile[0]->getKey());
    $revision->setDate(date('c'));
    $revision->setChanges("r2");
    $revision->setItemId(0);
    $revision->setRevision(2);
    $this->ItemRevision->save($revision);

    $this->Item->addRevision($item, $revision);
    $lastRev = $this->Item->getLastRevision($item);
    $this->assertEquals($revision->getKey(), $lastRev->getKey());
    $this->assertEquals($revision->getChanges(), $lastRev->getChanges());

    $revision = new ItemRevisionDao();
    $revision->setUserId($usersFile[0]->getKey());
    $revision->setDate(date('c'));
    $revision->setChanges("r3");
    $revision->setItemId(0);
    $revision->setRevision(3);
    $this->ItemRevision->save($revision);

    $this->Item->addRevision($item, $revision);
    $lastRev = $this->Item->getLastRevision($item);
    $this->assertEquals($revision->getKey(), $lastRev->getKey());
    $this->assertEquals($revision->getChanges(), $lastRev->getChanges());

    // we now have revision:changes 1:r1, 2:r2, 3:r3
    // remove r3, check that lastrevision changes = r2
    $this->Item->removeRevision($item, $lastRev);
    $lastRev = $this->Item->getLastRevision($item);
    $this->assertEquals($lastRev->getRevision(), "2");
    $this->assertEquals($lastRev->getChanges(), "r2");

    // now we have 1:r1, 2:r2
    // remove r1, check that lastrevision changes = r2 and revision = 1
    $rev1 = $this->Item->getRevision($item, 1);
    $this->Item->removeRevision($item, $rev1);
    $lastRev = $this->Item->getLastRevision($item);
    $this->assertEquals($lastRev->getRevision(), "1");
    $this->assertEquals($lastRev->getChanges(), "r2");

    // now we have 1:r2
    // remove r2, check that there are no revisions
    $this->Item->removeRevision($item, $lastRev);
    $lastRev = $this->Item->getLastRevision($item);
    $this->assertFalse($lastRev);
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
    $rcount = count($oldRevs);
    $this->assertTrue($rcount > 0);
    $this->assertEquals($rcount, count($newRevs));

    for($i = 0; $i < $rcount; $i++)
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
      $bcount = count($oldBitstreams);
      $this->assertEquals($bcount, count($newBitstreams));
      for($b = 0; $b < $bcount; $b++)
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
