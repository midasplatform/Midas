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
/** FolderpolicyuserModelTest*/
class FolderpolicyuserModelTest extends DatabaseTestCase
  {
  /** init tests*/
  public function setUp()
    {
    $this->setupDatabase(array());
    $this->_models = array(
      'Folderpolicyuser'
    );
    $this->_daos = array();
    parent::setUp();
    }

  /** testCreatePolicyAndGetPolicy*/
  public function testCreatePolicyAndGetPolicy()
    {
    $folderFile = $this->loadData('Folder', 'default');
    $usersFile = $this->loadData('User', 'default');
    $policy = $this->Folderpolicyuser->createPolicy($usersFile[0], $folderFile[5], 1);
    $this->assertEquals(true, $policy->saved);
    $policy = $this->Folderpolicyuser->getPolicy($usersFile[0], $folderFile[5]);
    $this->assertNotEquals(false, $policy);
    }
  }
