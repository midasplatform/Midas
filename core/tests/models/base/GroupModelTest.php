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
