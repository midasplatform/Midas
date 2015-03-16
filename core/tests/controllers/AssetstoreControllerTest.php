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

/** test assetstore controller */
class Core_AssetstoreControllerTest extends ControllerTestCase
{
    protected $nullUserDao;
    protected $nonAdminUserDao;
    protected $AdminUserDao;
    protected $testAssetstoreDao;
    protected $testAssetstoreAdditionalPath;

    /** init tests */
    public function setUp()
    {
        $this->setupDatabase(array('default'));
        $this->_models = array('Assetstore', 'Bitstream', 'User');
        $this->_daos = array('Assetstore', 'User');
        parent::setUp();
        $this->loadUsers();

        // create another assetstore
        $testAssetstoreBase = $this->getTempDirectory().'/test/';
        $testAssetstoreBase = str_replace('tests/../', '', $testAssetstoreBase);
        $testAssetstoreBase = str_replace('//', '/', $testAssetstoreBase);
        $testAssetstore2 = $testAssetstoreBase.'/assetstore2';
        if (!is_dir($testAssetstore2)) {
            mkdir($testAssetstore2);
        }
        $testAssetstoreAdditionalPath = $testAssetstoreBase.'/additionalpathassetstore2';
        if (!is_dir($testAssetstoreAdditionalPath)) {
            mkdir($testAssetstoreAdditionalPath);
        }
        $this->testAssetstoreAdditionalPath = $testAssetstoreAdditionalPath;

        $testAssetstoreDao = new AssetstoreDao();
        $testAssetstoreDao->setName('testassetstore2');
        $testAssetstoreDao->setPath($testAssetstore2);
        $testAssetstoreDao->setType(MIDAS_ASSETSTORE_LOCAL);
        $this->Assetstore->save($testAssetstoreDao);
        $this->testAssetstoreDao = $testAssetstoreDao;
    }

    /** tearDown tester method. */
    public function tearDown()
    {
        $this->Assetstore->delete($this->testAssetstoreDao);
        parent::tearDown();
    }

    /** helper method, load the 3 different user daos. */
    protected function loadUsers()
    {
        $usersFile = $this->loadData('User', 'default');
        $this->nullUserDao = null;
        foreach ($usersFile as $userDao) {
            if ($userDao->getFirstname() === 'Admin') {
                $this->adminUserDao = $userDao;
            } elseif ($userDao->getFirstname() === 'FirstName1') {
                $this->nonAdminUserDao = $userDao;
            }
        }
    }

    /** helper method, ensures only admins can call the action. */
    protected function ensureAdminRequired($pageURI)
    {
        // first try to bring up the page without logging in, should get an exception
        $withException = true;
        $this->params = array();
        $this->resetAll();
        $this->getRequest()->setMethod('POST');
        $this->dispatchUrl($pageURI, $this->nullUserDao, $withException);

        // now login with a non-admin account, should get an exception
        $this->resetAll();
        $this->params = array();
        $this->getRequest()->setMethod('POST');
        $this->dispatchUrl($pageURI, $this->nonAdminUserDao, $withException);

        // now login with an admin account
        $this->resetAll();
        $this->params = array();
        $this->getRequest()->setMethod('POST');
        $this->dispatchUrl($pageURI, $this->adminUserDao);
    }

    /** test defaultassetstore action */
    public function testDefaultassetstoreAction()
    {
        $pageURI = '/assetstore/defaultassetstore';
        $this->ensureAdminRequired($pageURI);

        // get the default assetstore
        $initialDefaultAssetstoreDao = $this->Assetstore->getDefault();

        // set the second assetstore as default
        $this->resetAll();
        $this->params = array();
        $this->params['submitDefaultAssetstore'] = 'submitDefaultAssetstore';
        $this->params['element'] = $this->testAssetstoreDao->getKey();
        $this->getRequest()->setMethod('POST');
        $this->dispatchUrl($pageURI, $this->adminUserDao);
        $response = json_decode($this->getBody());
        $this->assertEquals(1, $response[0], "Expected true json response");

        $defaultAssetstoreDao = $this->Assetstore->getDefault();

        $juggleTypes = true;
        $this->assertTrue(
            $this->Assetstore->compareDao($defaultAssetstoreDao, $this->testAssetstoreDao, $juggleTypes),
            'New default assetstore is not the real default assetstore'
        );

        // now set back to the original default
        $this->resetAll();
        $this->params = array();
        $this->params['submitDefaultAssetstore'] = 'submitDefaultAssetstore';
        $this->params['element'] = $initialDefaultAssetstoreDao->getKey();
        $this->getRequest()->setMethod('POST');
        $this->dispatchUrl($pageURI, $this->adminUserDao);
        $response = json_decode($this->getBody());
        $this->assertEquals(1, $response[0], "Expected true json response");

        $defaultAssetstoreDao = $this->Assetstore->getDefault();
        $this->assertTrue(
            $this->Assetstore->compareDao($defaultAssetstoreDao, $initialDefaultAssetstoreDao, $juggleTypes),
            'New default assetstore is not the real default assetstore'
        );

        // now don't send a submitDefaultAssetstore param and be sure we get an error
        $this->resetAll();
        $this->params = array();
        $this->getRequest()->setMethod('POST');
        $this->dispatchUrl($pageURI, $this->adminUserDao);
        $response = json_decode($this->getBody());
        $this->assertEquals("", $response[0], "Expected false json response");

        // now don't send a submitDefaultAssetstore param and be sure we get an error
        $this->resetAll();
        $this->params = array();
        $this->params['submitDefaultAssetstore'] = 'submitDefaultAssetstore';
        //$this->params['element'] = $initialDefaultAssetstoreDao->getKey();
        $this->getRequest()->setMethod('POST');
        $this->dispatchUrl($pageURI, $this->adminUserDao);
        $response = json_decode($this->getBody());
        $this->assertEquals("", $response[0], "Expected false json response");
    }

    /** test delete action */
    public function testDeleteAction()
    {
        $pageURI = '/assetstore/delete';
        $this->ensureAdminRequired($pageURI);

        $testAssetstoreId = $this->testAssetstoreDao->getKey();

        // delete the assetstore via the controller
        $this->resetAll();
        $this->params = array();
        $this->params['assetstoreId'] = $testAssetstoreId;
        $this->getRequest()->setMethod('POST');
        $this->dispatchUrl($pageURI, $this->adminUserDao);
        $response = json_decode($this->getBody());
        $this->assertEquals(1, $response[0], "Expected true json response");

        // try to load and be sure it is deleted
        $testAssetstoreDao = $this->Assetstore->load($testAssetstoreId);
        $this->assertFalse($testAssetstoreDao);
    }

    /** helper method, sends a request to assetstore controller */
    protected function dispatchRequestJson($pageURI, $params)
    {
        $this->resetAll();
        $this->params = $params;
        $this->getRequest()->setMethod('POST');
        $this->dispatchUrl($pageURI, $this->adminUserDao);
        $response = json_decode($this->getBody());

        return $response;
    }

    /** helper method, expects a false response */
    protected function expectFalseJson($pageURI, $params)
    {
        $response = $this->dispatchRequestJson($pageURI, $params);
        $this->assertEquals("", $response[0], "Expected false json response");
    }

    /** helper method, expects a true response */
    protected function expectTrueJson($pageURI, $params)
    {
        $response = $this->dispatchRequestJson($pageURI, $params);
        $this->assertEquals(1, $response[0], "Expected true json response");

        return $response;
    }

    /** helper method, expects an 'error' key */
    protected function expectErrorJson($pageURI, $params)
    {
        $response = $this->dispatchRequestJson($pageURI, $params);
        $this->assertTrue(isset($response->error));
    }

    /** test edit action */
    public function testEditAction()
    {
        $pageURI = '/assetstore/edit';
        $this->ensureAdminRequired($pageURI);

        $testAssetstoreId = $this->testAssetstoreDao->getKey();
        $testAssetstoreName = $this->testAssetstoreDao->getName();
        $testAssetstorePath = $this->testAssetstoreDao->getPath();
        $testAssetstoreNewName = "anewname";

        // get the default assetstore
        $defaultAssetstoreDao = $this->Assetstore->getDefault();
        $defaultAssetstoreName = $defaultAssetstoreDao->getName();
        $defaultAssetstorePath = $defaultAssetstoreDao->getPath();

        // test error conditions first

        // don't send ID
        $params = array();
        $this->expectFalseJson($pageURI, $params);

        // don't send name
        $params = array("assetstoreId" => $testAssetstoreId);
        $this->expectFalseJson($pageURI, $params);

        // don't send path
        $params = array("assetstoreId" => $testAssetstoreId, "assetstoreName" => $testAssetstoreName);
        $this->expectFalseJson($pageURI, $params);

        // send a bad path
        $params = array(
            "assetstoreId" => $testAssetstoreId,
            "assetstoreName" => $testAssetstoreName,
            "assetstorePath" => '/this/path/probably/will/not/exist',
        );
        $this->expectFalseJson($pageURI, $params);

        // try to edit to same name as default
        $params = array(
            "assetstoreId" => $testAssetstoreId,
            "assetstoreName" => $defaultAssetstoreName,
            "assetstorePath" => $testAssetstorePath,
        );
        $this->expectFalseJson($pageURI, $params);

        // try to edit to same path as default
        $params = array(
            "assetstoreId" => $testAssetstoreId,
            "assetstoreName" => $testAssetstoreName,
            "assetstorePath" => $defaultAssetstorePath,
        );
        $this->expectFalseJson($pageURI, $params);

        // edit name
        $params = array(
            "assetstoreId" => $testAssetstoreId,
            "assetstoreName" => $testAssetstoreNewName,
            "assetstorePath" => $testAssetstorePath,
        );
        $this->expectTrueJson($pageURI, $params);
        $updatedTestAssetstore = $this->Assetstore->load($testAssetstoreId);
        $this->assertEquals($updatedTestAssetstore->getName(), $testAssetstoreNewName);

        // edit path
        $params = array(
            "assetstoreId" => $testAssetstoreId,
            "assetstoreName" => $testAssetstoreNewName,
            "assetstorePath" => $this->testAssetstoreAdditionalPath,
        );
        $this->expectTrueJson($pageURI, $params);
        $updatedTestAssetstore = $this->Assetstore->load($testAssetstoreId);
        $this->assertEquals($updatedTestAssetstore->getPath(), $this->testAssetstoreAdditionalPath);

        // edit name and path
        $params = array(
            "assetstoreId" => $testAssetstoreId,
            "assetstoreName" => $testAssetstoreName,
            "assetstorePath" => $testAssetstorePath,
        );
        $this->expectTrueJson($pageURI, $params);
        $updatedTestAssetstore = $this->Assetstore->load($testAssetstoreId);
        $this->assertEquals($updatedTestAssetstore->getName(), $testAssetstoreName);
        $this->assertEquals($updatedTestAssetstore->getPath(), $testAssetstorePath);
    }

    /** test add action */
    public function testAddAction()
    {
        $pageURI = '/assetstore/add';
        $this->ensureAdminRequired($pageURI);

        // get the default assetstore
        $defaultAssetstoreDao = $this->Assetstore->getDefault();
        $defaultAssetstoreName = $defaultAssetstoreDao->getName();
        $defaultAssetstorePath = $defaultAssetstoreDao->getPath();

        $newAssetstoreName = "anewname";

        // test error conditions first

        // try to add as same name as default
        $params = array(
            "name" => $defaultAssetstoreName,
            "basedirectory" => $this->testAssetstoreAdditionalPath,
            "assetstoretype" => '0',
        );
        $this->expectErrorJson($pageURI, $params);

        // try to add as same path as default
        $params = array(
            "name" => $newAssetstoreName,
            "basedirectory" => $defaultAssetstorePath,
            "assetstoretype" => '0',
        );
        $this->expectErrorJson($pageURI, $params);

        // add and check saved values
        $params = array(
            "name" => $newAssetstoreName,
            "basedirectory" => $this->testAssetstoreAdditionalPath,
            "assetstoretype" => '1',
        );
        $response = $this->dispatchRequestJson($pageURI, $params);
        $this->assertTrue(isset($response->assetstore_id), "Expected error key assetstore_id in response");
        $createdAssetstoreDao = $this->Assetstore->load($response->assetstore_id);
        $this->assertEquals($createdAssetstoreDao->getName(), $newAssetstoreName);
        $this->assertEquals($createdAssetstoreDao->getPath(), $this->testAssetstoreAdditionalPath);
        $this->assertEquals($createdAssetstoreDao->getType(), '1');

        // delete the newly added assetstore
        $this->Assetstore->delete($createdAssetstoreDao);
    }

    /**
     * Test the move bitstreams between assetstores dialog
     */
    public function testMoveDialog()
    {
        $pageURI = '/assetstore/movedialog?srcAssetstoreId='.$this->testAssetstoreDao->getKey();
        $defaultAssetstore = $this->Assetstore->getDefault();

        $this->ensureAdminRequired($pageURI);

        $this->assertNotEquals($defaultAssetstore->getKey(), $this->testAssetstoreDao->getKey());
        $this->assertQueryContentContains(
            'option[value="'.$defaultAssetstore->getKey().'"]',
            $defaultAssetstore->getName()
        );
        $this->assertNotQuery('option[value="'.$this->testAssetstoreDao->getKey().'"]');
    }

    /**
     * Testing moving bitstreams from one assetstore to another
     */
    public function testMoveContents()
    {
        $defaultAssetstore = $this->Assetstore->getDefault();
        $pageURI = '/assetstore/movecontents?srcAssetstoreId='.$defaultAssetstore->getKey();
        $pageURI .= '&dstAssetstoreId='.$this->testAssetstoreDao->getKey();
        $bitstream = $this->Bitstream->getByChecksum('f283bc88b24491ba85c65ba960642753');
        $oldPath = $bitstream->getFullPath();
        $newPath = $this->testAssetstoreDao->getPath().'/'.$bitstream->getPath();

        if (!is_dir(dirname($oldPath))) {
            mkdir(dirname($oldPath), 0777, true);
        }
        touch($oldPath);

        $this->assertTrue(is_file($oldPath));
        $this->assertFalse(is_file($newPath));

        $this->ensureAdminRequired($pageURI);

        $this->assertFalse(is_file($oldPath));
        $this->assertTrue(is_file($newPath));
    }
}
