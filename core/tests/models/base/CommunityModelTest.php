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
