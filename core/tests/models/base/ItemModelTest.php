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
    $this->assertEquals($revisionsFile[1]->getKey(), $revision->getKey());
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
