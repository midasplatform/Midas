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

/** test licenses controller */
class Core_LicensesControllerTest extends ControllerTestCase
{
    /** Setup before each test */
    public function setUp()
    {
        $this->setupDatabase(array('default'));
        $this->_models = array('Item', 'ItemRevision', 'License', 'User');
        parent::setUp();
    }

    /** Test listing the current licenses */
    public function testAllAction()
    {
        $usersFile = $this->loadData('User', 'default');
        $licensesFile = $this->loadData('License', 'default');
        $normalUser = $this->User->load($usersFile[0]->getKey());
        $adminUser = $this->User->load($usersFile[2]->getKey());

        $this->dispatchUrI('/licenses/all', $normalUser, true);

        $this->resetAll();
        $this->dispatchUrI('/licenses/all', $adminUser);
        $this->assertController('licenses');
        $this->assertAction('all');
        $this->assertQueryCount('form.existingLicense', count($licensesFile));
        $this->assertQueryCount('form.newLicense', 1);
    }

    /** Test creating a new license */
    public function testCreateAction()
    {
        $usersFile = $this->loadData('User', 'default');
        $normalUser = $this->User->load($usersFile[0]->getKey());
        $adminUser = $this->User->load($usersFile[2]->getKey());

        $initialCount = count($this->License->getAll());

        $this->dispatchUrI('/licenses/create', $normalUser, true);

        $this->resetAll();
        $this->dispatchUrI('/licenses/create?name=hello&fulltext=world', $adminUser);
        $this->assertEquals(count($this->License->getAll()), $initialCount + 1);
        $resp = json_decode($this->getBody());
        $this->assertTrue($resp[0] != false);
    }

    /** Test changing an existing license */
    public function testSaveAction()
    {
        $usersFile = $this->loadData('User', 'default');
        $normalUser = $this->User->load($usersFile[0]->getKey());
        $adminUser = $this->User->load($usersFile[2]->getKey());

        $all = $this->License->getAll();
        $license = $all[0];

        $this->dispatchUrI('/licenses/save', $normalUser, true);

        $this->resetAll();
        $this->getRequest()->setMethod('POST');
        $this->params['name'] = 'changed name';
        $this->params['fulltext'] = 'changed the fulltext';
        $this->params['licenseId'] = $license->getKey();
        $this->dispatchUrI('/licenses/save', $adminUser);
        $resp = json_decode($this->getBody());
        $this->assertTrue($resp[0] != false);

        $license = $this->License->load($license->getKey());
        $this->assertEquals($license->getName(), 'changed name');
        $this->assertEquals($license->getFulltext(), 'changed the fulltext');
    }

    /** Test deletion of a license */
    public function testDeleteAction()
    {
        $usersFile = $this->loadData('User', 'default');
        $revisionsFile = $this->loadData('ItemRevision', 'default');
        $normalUser = $this->User->load($usersFile[0]->getKey());
        $adminUser = $this->User->load($usersFile[2]->getKey());

        $all = $this->License->getAll();
        $license1 = $all[0];
        $license2 = $all[1];
        $initialCount = count($all);

        $revision1 = $this->ItemRevision->load($revisionsFile[0]->getKey());
        $revision2 = $this->ItemRevision->load($revisionsFile[1]->getKey());
        $revision3 = $this->ItemRevision->load($revisionsFile[2]->getKey());

        $this->assertEquals($revision1->getLicenseId(), $license1->getKey());
        $this->assertEquals($revision2->getLicenseId(), $license2->getKey());
        $this->assertEquals($revision3->getLicenseId(), null);

        $this->dispatchUrI('/licenses/delete', $normalUser, true);

        $this->resetAll();
        $this->getRequest()->setMethod('POST');
        $this->params['licenseId'] = $license1->getKey();
        $this->dispatchUrI('/licenses/delete', $adminUser);
        $resp = json_decode($this->getBody());
        $this->assertTrue($resp[0] != false);

        // Make sure license record was deleted
        $this->assertEquals(count($this->License->getAll()), $initialCount - 1);

        // Make sure revisions pointing to that license had their license field nullified
        $revision1 = $this->ItemRevision->load($revisionsFile[0]->getKey());
        $revision2 = $this->ItemRevision->load($revisionsFile[1]->getKey());
        $revision3 = $this->ItemRevision->load($revisionsFile[2]->getKey());
        $this->assertEquals($revision1->getLicenseId(), null);
        $this->assertEquals($revision2->getLicenseId(), $license2->getKey());
        $this->assertEquals($revision3->getLicenseId(), null);
    }
}
