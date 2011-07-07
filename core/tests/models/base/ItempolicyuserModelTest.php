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
/** ItempolicyuserModelTest*/
class ItempolicyuserModelTest extends DatabaseTestCase
  {
  /** init tests*/
  public function setUp()
    {
    $this->setupDatabase(array());
    $this->_models = array(
      'Itempolicyuser'
    );
    $this->_daos = array();
    parent::setUp();
    }

  /** testCreatePolicyAndGetPolicy*/
  public function testCreatePolicyAndGetPolicy()
    {
    $usersFile = $this->loadData('User', 'default');
    $itemsFile = $this->loadData('Item', 'default');
    $policy = $this->Itempolicyuser->createPolicy($usersFile[0], $itemsFile[1], 1);
    $this->assertEquals(true, $policy->saved);
    $policy = $this->Itempolicyuser->getPolicy($usersFile[0], $itemsFile[1]);
    $this->assertNotEquals(false, $policy);
    }
  }
