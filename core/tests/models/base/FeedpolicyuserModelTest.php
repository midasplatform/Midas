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
/** FeedpolicyuserModelTest*/
class FeedpolicyuserModelTest extends DatabaseTestCase
  {
  /** init test*/
  public function setUp()
    {
    $this->setupDatabase(array());
    $this->_models = array(
      'Feedpolicyuser'
    );
    $this->_daos = array();
    parent::setUp();
    }

  /** testCreatePolicyAndGetPolicy */
  public function testCreatePolicyAndGetPolicy()
    {
    $feedsFile = $this->loadData('Feed', 'default');
    $usersFile = $this->loadData('User', 'default');
    $policy = $this->Feedpolicyuser->createPolicy($usersFile[0], $feedsFile[5], 1);
    $this->assertEquals(true, $policy->saved);
    $policy = $this->Feedpolicyuser->getPolicy($usersFile[0], $feedsFile[5]);
    $this->assertNotEquals(false, $policy);
    }
  }