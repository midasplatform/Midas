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
