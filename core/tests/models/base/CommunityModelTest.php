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
/** Community model test*/
class CommunityModelTest extends DatabaseTestCase
  {
  /** init tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default'));
    $this->_models = array('Community', 'Folder', 'User');
    $this->_daos = array();
    parent::setUp();
    }

  /** test create community */
  public function testCreateCommunity()
    {
    $prevCount = count($this->Community->getAll());
    $usersFile = $this->loadData('User', 'default');
    $user = $this->User->load($usersFile[0]->getKey());
    $community = $this->Community->createCommunity('test community', 'test description',
                                                   0, $user, null, '');
    $newCount = count($this->Community->getAll());
    $this->assertTrue($community != false);
    $this->assertEquals($prevCount + 1, $newCount, 'Community count did not increase');

    $folder = $this->Folder->load($community->getFolderId());
    $this->assertEquals($folder->getName(), 'community_'.$community->getKey());
    }

  }
