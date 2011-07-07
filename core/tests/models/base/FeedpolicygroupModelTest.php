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

/** FeedpolicygroupModelTest*/
class FeedpolicygroupModelTest extends DatabaseTestCase
  {
  /** init tests*/
  public function setUp()
    {
    $this->setupDatabase(array());
    $this->_models = array(
      'Feedpolicygroup'
    );
    $this->_daos = array();
    parent::setUp();
    }
  /** testCreatePolicyAndGetPolicy */
  public function testCreatePolicyAndGetPolicy()
    {
    $feedsFile = $this->loadData('Feed', 'default');
    $groupsFile = $this->loadData('Group', 'default');
    $policy = $this->Feedpolicygroup->createPolicy($groupsFile[0], $feedsFile[0], 1);
    $this->assertEquals(true, $policy->saved);
    $policy = $this->Feedpolicygroup->getPolicy($groupsFile[0], $feedsFile[0]);
    $this->assertNotEquals(false, $policy);
    }
  }
