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
/** GroupModelTest*/
class GroupModelTest extends DatabaseTestCase
  {
  /** init tests*/
  public function setUp()
    {
    $this->setupDatabase(array());
    $this->_models = array(
      'Group'
    );
    $this->_daos = array();
    parent::setUp();
    }

  /** testCreateAndAddUser*/  
  public function testCreateAndAddUser()
    {
    $usersFile = $this->loadData('User', 'default');
    $communitiesFile = $this->loadData('Community', 'default');
    $newgroup = $this->Group->createGroup($communitiesFile[0], "testName");
    $this->assertNotEquals(false, $newgroup);
    $this->assertEquals("testName", $newgroup->getName());

    $this->Group->addUser($newgroup, $usersFile[0]);
    $users = $newgroup->getUsers();
    $this->assertNotEquals(false, $users);
    $this->assertEquals($usersFile[0]->getKey(), $users[0]->getKey());
    }
  }
