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

/**
 * Tests the Import Controller that drives the local uploader
 */
class Core_ImportControllerTest extends ControllerTestCase
{
    /** init tests */
    public function setUp()
    {
        $this->setupDatabase(array('default'));
        $this->_models = array('Assetstore', 'User', 'Folder', 'Item');
        $this->_daos = array('Assetstore', 'User', 'Folder', 'Item');
        parent::setUp();
        $this->loadUsers();
        $this->createTestHierarchy();
    }

    /** tearDown tester method. */
    public function tearDown()
    {
        $this->destroyTestHierarchy();
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
    protected function ensureAdminRequired($pageURI, $method)
    {
        // first try to bring up the page without logging in, should get an exception
        $withException = true;
        $this->params = array();
        $this->resetAll();
        $this->getRequest()->setMethod($method);
        $this->dispatchUrl($pageURI, $this->nullUserDao, $withException);

        // now login with a non-admin account, should get an exception
        $this->resetAll();
        $this->params = array();
        $this->getRequest()->setMethod($method);
        $this->dispatchUrl($pageURI, $this->nonAdminUserDao, $withException);

        // now login with an admin account
        $this->resetAll();
        $this->params = array();
        $this->getRequest()->setMethod($method);
        $this->dispatchUrl($pageURI, $this->adminUserDao);
    }

    /**
     * Create a test folder tree for testing import functionality.
     */
    protected function createTestHierarchy()
    {
        $this->baseDirectory = $this->getTempDirectory().'/test/A';
        $this->subDirectory1 = $this->baseDirectory.'/1';
        $this->subDirectory2 = $this->baseDirectory.'/2';
        $this->subDirectory3 = $this->baseDirectory.'/3';
        $this->subFile = $this->baseDirectory.'/foo.txt';
        $this->subSubDirectory1 = $this->subDirectory1.'/B';
        $this->subSubFile1 = $this->subSubDirectory1.'/bar.txt';
        $this->subSubFile2 = $this->subSubDirectory1.'/baz.txt';
        @mkdir($this->baseDirectory);
        @mkdir($this->subDirectory1);
        @mkdir($this->subDirectory2);
        @mkdir($this->subDirectory3);
        @mkdir($this->subSubDirectory1);
        $this->writeFileWithText($this->subFile, "Test text.\n");
        $this->writeFileWithText($this->subSubFile1, "More text for testing.\n");
        $this->writeFileWithText($this->subSubFile2, "Even more text for testing.\n");
    }

    /**
     * Function for tearing down the test hierarchy. It is assumed that
     * createTestHierarchy has been run before it is called.
     */
    protected function destroyTestHierarchy()
    {
        unlink($this->subSubFile2);
        unlink($this->subSubFile1);
        unlink($this->subFile);
        rmdir($this->subSubDirectory1);
        rmdir($this->subDirectory3);
        rmdir($this->subDirectory2);
        rmdir($this->subDirectory1);
        rmdir($this->baseDirectory);
    }

    /**
     * Used to write plain text to a file for the purpose of testing.
     */
    protected function writeFileWithText($filename, $text)
    {
        $fh = fopen($filename, 'w');
        fwrite($fh, $text);
        fclose($fh);
    }

    /**
     * Test that the index is rendered properly and requires administrative
     * access. We check if the progress and status divs are there.
     */
    public function testIndexAction()
    {
        $this->ensureAdminRequired('import/', 'GET');
        $this->assertQuery('#progress');
        $this->assertQuery('#progress_status');
    }

    /**
     * Test that the import works correctly
     */
    public function testImportAction()
    {
        $pageToTest = 'import/import';

        $this->ensureAdminRequired($pageToTest, 'POST');

        // Test invalid form
        $this->resetAll();
        $this->params = array();
        $this->params['validate'] = 1;
        $this->getRequest()->setMethod('POST');
        $this->dispatchUrl($pageToTest, $this->adminUserDao);
        $invalidFormResponse = json_decode($this->getBody());
        $this->assertEquals(
            'The form is invalid. Missing values.',
            $invalidFormResponse->error,
            'Expected a validation error.'
        );

        // Test validating a valid form
        $this->resetAll();
        $this->getRequest()->setMethod('POST');
        $this->params = array();
        $this->params['uploadid'] = "1109853050";
        $this->params['inputdirectory'] = $this->getTempDirectory().'/test/A';
        $this->params['importassetstoretype'] = '0';
        $this->params['assetstore'] = $this->Assetstore->getDefault()->getKey();
        $this->params['importemptydirectories'] = '1';
        $this->params['importFolder'] = '1007';
        $this->params['importstop'] = 'Stop import';
        $this->params['validate'] = '1';
        $this->dispatchUrl($pageToTest, $this->adminUserDao);
        $validateFormResponse = json_decode($this->getBody());
        $this->assertEquals('validate', $validateFormResponse->stage, 'Expected a response of successful validation.');

        // Testing initializing a valid form
        $this->resetAll();
        $this->getRequest()->setMethod('POST');
        $this->params = array();
        $this->params['uploadid'] = "1109853050";
        $this->params['inputdirectory'] = $this->getTempDirectory().'/test/A';
        $this->params['importassetstoretype'] = '0';
        $this->params['assetstore'] = $this->Assetstore->getDefault()->getKey();
        $this->params['importemptydirectories'] = '1';
        $this->params['importFolder'] = '1007';
        $this->params['importstop'] = 'Stop import';
        $this->params['initialize'] = '1';
        $this->dispatchUrl($pageToTest, $this->adminUserDao);
        $initializeFormResponse = json_decode($this->getBody());
        $this->assertEquals(
            'initialize',
            $initializeFormResponse->stage,
            'Expected a response of successful initialization.'
        );
        $this->assertEquals(
            3,
            $initializeFormResponse->totalfiles,
            'Incorrect count from local upload initialization.'
        );

        // Testing import
        $this->resetAll();
        $this->getRequest()->setMethod('POST');
        $this->params = array();
        $this->params['uploadid'] = "1109853050";
        $this->params['inputdirectory'] = $this->getTempDirectory().'/test/A';
        $this->params['importassetstoretype'] = '0';
        $this->params['assetstore'] = $this->Assetstore->getDefault()->getKey();
        $this->params['importemptydirectories'] = '1';
        $this->params['importFolder'] = '1007';
        $this->params['importstop'] = 'Stop import';
        $this->params['totalfiles'] = '3';
        $this->dispatchUrl($pageToTest, $this->adminUserDao);
        $importFormResponse = json_decode($this->getBody());
        $this->assertEquals('Import successful.', $importFormResponse->message, 'Actual local import failed.');
        $folder = $this->Folder->load(1007);
        $subFolders = $folder->getFolders();
        $subItems = $folder->getItems();
        $subFoldersMap = array();
        foreach ($subFolders as $sub) {
            $subFoldersMap[$sub->getName()] = $sub;
        }
        $subSubFolders = $subFoldersMap['1']->getFolders();
        $subSubItems = $subSubFolders[0]->getItems();
        $subSubItemsMap = array();
        foreach ($subSubItems as $sub) {
            $subSubItemsMap[$sub->getName()] = $sub;
        }
        $this->assertEquals('1', $subFoldersMap['1']->getName(), 'Subfolder improperly imported via local import.');
        $this->assertEquals('2', $subFoldersMap['2']->getName(), 'Subfolder improperly imported via local import.');
        $this->assertEquals('3', $subFoldersMap['3']->getName(), 'Subfolder improperly imported via local import.');
        $this->assertEquals('foo.txt', $subItems[0]->getName(), 'SubItem improperly imported via local import.');
        $this->assertEquals('B', $subSubFolders[0]->getName(), 'SubSubfolder improperly imported via local import.');
        $this->assertEquals(
            'bar.txt',
            $subSubItemsMap['bar.txt']->getName(),
            'SubSubItem improperly imported via local import.'
        );
        $this->assertEquals(
            'baz.txt',
            $subSubItemsMap['baz.txt']->getName(),
            'SubSubItem improperly imported via local import.'
        );
    }
}
