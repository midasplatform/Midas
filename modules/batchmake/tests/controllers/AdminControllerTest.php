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

// need to include the module constant for this test
require_once BASE_PATH.'/modules/batchmake/constant/module.php';
require_once BASE_PATH.'/modules/batchmake/tests/controllers/ControllerTestCase.php';

/** Admin controller tests for the batchmake module. */
class Batchmake_AdminControllerTest extends Batchmake_ControllerTestCase
{
    /** Set up tests. */
    public function setUp()
    {
        $this->setupDatabase(array('default'));
        $this->_daos = array('User');
        $this->_models = array('User');
        $this->enabledModules = array('batchmake');
        parent::setUp();
    }

    /** Test index action. */
    public function testIndexAction()
    {
        // first try to bring up the page without logging in, should get an exception
        $usersFile = $this->loadData('User', 'default');
        $nullUserDao = null;

        /** @var UserDao $userDao */
        foreach ($usersFile as $userDao) {
            if ($userDao->getFirstname() === 'Admin') {
                $adminUserDao = $userDao;
            } elseif ($userDao->getFirstname() === 'FirstName1') {
                $nonAdminUserDao = $userDao;
            }
        }

        $withException = true;
        $page = '/batchmake/admin/index';
        $this->params = array();
        $this->getRequest()->setMethod('GET');
        $this->dispatchUrl($page, $nullUserDao, $withException);

        // now login with a non-admin account, should get an exception
        $this->resetAll();
        $this->params = array();
        $this->getRequest()->setMethod('GET');
        $this->dispatchUrl($page, $nonAdminUserDao, $withException);

        // now login with an admin account
        $this->resetAll();
        $this->params = array();
        $this->getRequest()->setMethod('GET');
        $this->dispatchUrl($page, $adminUserDao);

        $body = $this->getBody();

        $this->assertModule('batchmake');
        $this->assertController('admin');
        $this->assertAction('index');

        if (strpos($body, 'BatchMake Module Configuration') === false) {
            $this->fail('Unable to find the body element');
        }

        $this->assertQuery('form');
    }
}
