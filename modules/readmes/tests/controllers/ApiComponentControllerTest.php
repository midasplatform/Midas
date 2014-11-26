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

require_once BASE_PATH.'/core/tests/controllers/api/RestCallMethodsTestCase.php';

/** config controller test */
class Readmes_ApiComponentControllerTest extends RestCallMethodsTestCase
{
    /** set up tests */
    public function setUp()
    {
        $this->enabledModules = array('readmes');

        // There is copy-pasta in the folders because the setUp function would
        // not cooperate. We should fix that one day.

        parent::setUp();
    }

    /** test readme on folder */
    public function testFolderReadme()
    {
        $this->enabledModules = array('readmes');
        $this->resetAll();
        $this->setupDatabase(array('default'));
        Zend_Registry::set('modulesEnable', array());
        Zend_Registry::set('notifier', new MIDAS_Notifier(false, null));
        $this->Community = MidasLoader::loadModel('Community');
        $this->Folder = MidasLoader::loadModel('Folder');
        $usersFile = $this->loadData('User', 'default');
        $adminUser = $this->User->load($usersFile[2]->getKey());

        $oldPath = BASE_PATH.'/modules/readmes/tests/data/readme.md';
        $dir = $this->getTempDirectory().'/'.$adminUser->getUserId().'/1002'; // private folder
        $newPath = $dir.'/readme.md';
        if (!file_exists($dir)) {
            mkdir($dir, 0700, true);
        }
        if (file_exists($newPath)) {
            unlink($newPath);
        }
        copy($oldPath, $newPath);
        $commFile = $this->loadData('Community', 'default');
        $comm = $this->Community->load($commFile[0]->getKey());

        $rootFolder = $comm->getFolder();
        $publicFolder = $this->Folder->createFolder('Public', '', $rootFolder);

        /** @var UploadComponent $uploadComponent */
        $uploadComponent = MidasLoader::loadComponent('Upload');
        $uploadComponent->createUploadedItem($adminUser, 'readme.md', $newPath, $publicFolder);

        $resp = $this->_callRestApi('GET', '/readmes/folder/'.$publicFolder->getKey());
        $this->_assertStatusOk($resp);
        $this->assertEquals($resp["body"]->data->text, "<p>This is a readme</p>\n");
    }

    /** test readme on community */
    public function testCommunityReadme()
    {
        $this->enabledModules = array('readmes');
        $this->resetAll();
        $this->setupDatabase(array('default'));
        Zend_Registry::set('modulesEnable', array());
        Zend_Registry::set('notifier', new MIDAS_Notifier(false, null));
        $this->Community = MidasLoader::loadModel('Community');
        $this->Folder = MidasLoader::loadModel('Folder');
        $usersFile = $this->loadData('User', 'default');
        $adminUser = $this->User->load($usersFile[2]->getKey());

        $oldPath = BASE_PATH.'/modules/readmes/tests/data/readme.md';
        $dir = $this->getTempDirectory().'/'.$adminUser->getUserId().'/1002'; // private folder
        $newPath = $dir.'/readme.md';
        if (!file_exists($dir)) {
            mkdir($dir, 0700, true);
        }
        if (file_exists($newPath)) {
            unlink($newPath);
        }
        copy($oldPath, $newPath);
        $commFile = $this->loadData('Community', 'default');
        $comm = $this->Community->load($commFile[0]->getKey());

        $rootFolder = $comm->getFolder();
        $publicFolder = $this->Folder->createFolder('Public', '', $rootFolder);

        /** @var UploadComponent $uploadComponent */
        $uploadComponent = MidasLoader::loadComponent('Upload');
        $uploadComponent->createUploadedItem($adminUser, 'readme.md', $newPath, $publicFolder);

        $resp = $this->_callRestApi('GET', '/readmes/community/'.$comm->getKey());
        $this->_assertStatusOk($resp);
        $this->assertEquals($resp["body"]->data->text, "<p>This is a readme</p>\n");
    }
}
