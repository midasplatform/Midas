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
/** Test for admin controllers */
class AdminControllerTest extends ControllerTestCase
  {
  /** init tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default'));
    $this->_models = array('User');
    $this->_daos = array('User');
    parent::setUp();
    }

  /** STUB: test index action */
  public function testIndexAction()
    {
    // Need this line to render admin index page
    Zend_Registry::set('configCore', array());

    $usersFile = $this->loadData('User', 'default');
    $user1 = $this->User->load($usersFile[0]->getKey());
    $adminUser = $this->User->load($usersFile[2]->getKey());

    // Should get empty body if we try to access admin controller unlogged
    $this->dispatchUrI('/admin', null);
    $this->assertController('admin');
    $this->assertAction('index');
    $body = $this->getBody();
    $this->assertTrue(empty($body));

    // If a non admin tries to access admin page, should throw exception
    $this->resetAll();
    $this->dispatchUrI('/admin', $user1, true);

    /*$this->resetAll();
    $this->dispatchUrI('/admin', $adminUser);
    $this->assertController('admin');
    $this->assertAction('index');*/
    }

  /** STUB: test show log action */
  public function testShowLogAction()
    {
    $usersFile = $this->loadData('User', 'default');
    $user1 = $this->User->load($usersFile[0]->getKey());
    $adminUser = $this->User->load($usersFile[2]->getKey());

    // Should get exception if we try to access logs while not logged in
    $this->dispatchUrI('/admin/showlog', null, true);

    // Should get exception if we try to access logs as non admin
    $this->resetAll();
    $this->dispatchUrI('/admin/showlog', $user1, true);

    // Should be able to see log page as admin user
    $this->resetAll();
    $this->dispatchUrI('/admin/showlog', $adminUser);
    $this->assertController('admin');
    $this->assertAction('showlog');
    }

  /** STUB: test dashboard action */
  public function testDashboardAction()
    {
    $usersFile = $this->loadData('User', 'default');
    $user1 = $this->User->load($usersFile[0]->getKey());
    $adminUser = $this->User->load($usersFile[2]->getKey());

    // Should get exception if we try to access logs while not logged in
    $this->dispatchUrI('/admin/dashboard', null, true);

    // Should get exception if we try to access logs as non admin
    $this->resetAll();
    $this->dispatchUrI('/admin/dashboard', $user1, true);

    // Should be able to see log page as admin user
    $this->resetAll();
    $this->dispatchUrI('/admin/dashboard', $adminUser);
    $this->assertController('admin');
    $this->assertAction('dashboard');
    }
  }
