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
/** ItempolicygroupModelTest*/
class ItempolicygroupModelTest extends DatabaseTestCase
  {
  /** init tests*/
  public function setUp()
    {
    $this->setupDatabase(array());
    $this->_models = array(
      'Itempolicygroup'
    );
    $this->_daos = array();
    parent::setUp();
    }

  /** testCreatePolicyAndGetPolicy*/
  public function testCreatePolicyAndGetPolicy()
    {
    $groupsFile = $this->loadData('Group', 'default');
    $itemsFile = $this->loadData('Item', 'default');
    $policy = $this->Itempolicygroup->createPolicy($groupsFile[0], $itemsFile[1], 1);
    $this->assertEquals(true, $policy->saved);
    $policy = $this->Itempolicygroup->getPolicy($groupsFile[0], $itemsFile[1]);
    $this->assertNotEquals(false, $policy);
    }
  }
