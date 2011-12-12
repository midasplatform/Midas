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

/** test feed controller*/
class FeedControllerTest extends ControllerTestCase
  {
  /** init tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default'));
    $this->_models = array('User', 'Feed');
    $this->_daos = array('User');
    parent::setUp();

    }

  /** test index*/
  public function testIndexAction()
    {
    $this->dispatchUrI("/feed");
    $this->assertController("feed");
    $this->assertAction("index");

    // test if we have the feed public
    $this->assertQuery("div.feedElement[element='1']");
    $this->assertNotQuery("div.feedElement[element='3']");

    $this->resetAll();
    // test when logged in
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());

    $this->dispatchUrI("/feed", $userDao);
    $this->assertController("feed");
    $this->assertAction("index");

    $this->assertQuery("div.feedElement[element='1']");
    $this->assertQuery("div.feedElement[element='3']");
    }

  /** test delete feed */
  public function testDeleteajaxAction()
    {
    // test if we get an error
    $this->dispatchUrI('/feed/deleteajax', null, true);

    $feedsFile = $this->loadData('Feed', 'default');
    $feedDao = $this->Feed->load($feedsFile[2]->getKey());
    $this->params['feed'] = $feedDao->getKey();
    $this->dispatchUrI('/feed/deleteajax', null);

    $feedDao = $this->Feed->load($feedDao->getKey());
    if($feedDao == false)
      {
      $this->fail('Should not be able to delete feed '.$feedDao->getKey());
      }

    $this->params['feed'] = $feedDao->getKey();
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $this->dispatchUrI('/feed/deleteajax', $userDao);

    $feedDao = $this->Feed->load($feedDao->getKey());
    if($feedDao != false)
      {
      $this->fail('Should be able to delete feed '.$feedDao->getKey());
      }

    $this->setupDatabase(array('default'));
    }
  }
