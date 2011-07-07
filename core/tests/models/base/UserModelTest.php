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
/** UserModelTest*/
class UserModelTest extends DatabaseTestCase
  {
  /** init tests*/
  public function setUp()
    {
    $this->setupDatabase(array());
    $this->_models = array('User');
    $this->_daos = array('User');
    parent::setUp();
    }

  /** testGetUserCommunities*/
  public function testGetUserCommunities()
    {
    $communitiesFile = $this->loadData('Community', 'default');

    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $communityDaos = $this->User->getUserCommunities($userDao);
    if(!in_array($communitiesFile[0], $communityDaos, false))
      {
      $this->fail('Unable to match community');
      }
    }

  /** testGetUserCommunitiesException*/
  public function testGetUserCommunitiesException()
    {
    try
      {
      $communityDaos = $this->User->getUserCommunities('test');
      }
    catch(Exception $expected) 
      {
      return;
      }
    $this->fail('An expected exception has not been raised.');
    }
  }
