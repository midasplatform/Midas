<?php
/*=========================================================================
 Midas Server
 Copyright Kitware SAS, 26 rue Louis Guérin, 69100 Villeurbanne, France.
 All rights reserved.
 For more information visit http://www.kitware.com/.

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
class Core_AdminControllerTest extends ControllerTestCase
{
    /** init tests */
    public function setUp()
    {
        $this->setupDatabase(array('default'));
        $this->_models = array('User');
        $this->_daos = array('User');
        parent::setUp();
    }

    /** test index action */
    public function testIndexAction()
    {
        // Need this line to render admin index page
        Zend_Registry::set('configCore', array());

        $usersFile = $this->loadData('User', 'default');
        $user1 = $this->User->load($usersFile[0]->getKey());
        $adminUser = $this->User->load($usersFile[2]->getKey());

        // Should get exception if we try to access admin controller unlogged
        $this->dispatchUrl('/admin', null, true);
        $this->assertController('error');
        $this->assertAction('error');

        // If a non admin tries to access admin page, should throw exception
        $this->resetAll();
        $this->dispatchUrl('/admin', $user1, true);

        $this->resetAll();
        $this->dispatchUrl('/admin', $adminUser);
        $this->assertController('admin');
        $this->assertAction('index');
    }

    /** test dashboard action */
    public function testDashboardAction()
    {
        $usersFile = $this->loadData('User', 'default');
        $user1 = $this->User->load($usersFile[0]->getKey());
        $adminUser = $this->User->load($usersFile[2]->getKey());

        // Should get exception if we try to access logs while not logged in
        $this->dispatchUrl('/admin/dashboard', null, true);

        // Should get exception if we try to access logs as non admin
        $this->resetAll();
        $this->dispatchUrl('/admin/dashboard', $user1, true);

        // Should be able to see log page as admin user
        $this->resetAll();
        $this->dispatchUrl('/admin/dashboard', $adminUser);
        $this->assertController('admin');
        $this->assertAction('dashboard');

        // Test integrity check action
        $this->resetAll();
        $this->dispatchUrl('/admin/integritycheck', $adminUser);
    }

    /**
     * Test removal of orphans in the tree
     */
    public function testRemoveOrphans()
    {
        $usersFile = $this->loadData('User', 'default');
        $user1 = $this->User->load($usersFile[0]->getKey());
        $adminUser = $this->User->load($usersFile[2]->getKey());
        $this->dispatchUrl('/admin/removeorphans', $user1, true);

        $this->resetAll();
        $this->dispatchUrl('/admin/removeorphans', $adminUser, true);

        $this->resetAll();
        $this->dispatchUrl('/admin/removeorphans?model=Blah', $adminUser, true);

        $this->resetAll();
        $this->dispatchUrl('/admin/removeorphans?model=Bitstream', $adminUser);
        $resp = json_decode($this->getBody());
        $this->assertEquals($resp->status, 'ok');
        $this->assertEquals($resp->message, 'Bitstream resources cleaned');
    }

    /**
     * Test the upgrade action
     */
    public function testUpgradeAction()
    {
        $usersFile = $this->loadData('User', 'default');
        $user1 = $this->User->load($usersFile[0]->getKey());
        $adminUser = $this->User->load($usersFile[2]->getKey());
        $this->dispatchUrl('/admin/upgrade', $user1, true);

        $this->resetAll();
        $this->dispatchUrl('/admin/upgrade', $adminUser);

        $this->resetAll();
        $this->getRequest()->setMethod('POST');
        $this->dispatchUrl('/admin/upgrade', $adminUser);
    }

    /**
     * Stub test for midas2 migration action
     */
    public function testMidas2MigrationAction()
    {
        $usersFile = $this->loadData('User', 'default');
        $user1 = $this->User->load($usersFile[0]->getKey());
        $adminUser = $this->User->load($usersFile[2]->getKey());
        $this->dispatchUrl('/admin/migratemidas2', $user1, true);

        $this->resetAll();
        $this->dispatchUrl('/admin/migratemidas2', $adminUser);
    }
}
