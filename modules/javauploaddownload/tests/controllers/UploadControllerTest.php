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

/** Test upload controller */
class Javauploaddownload_UploadControllerTest extends ControllerTestCase
{
    /** Initialize tests */
    public function setUp()
    {
        $this->enabledModules = array('javauploaddownload');
        $this->_daos = array('User');
        $this->_models = array('Item', 'User');
        parent::setUp();
    }

    /** Test upload controller gethttpuploadoffset action */
    public function testGethttpuploadoffsetAction()
    {
        $this->setupDatabase(array('default'));
        $usersFile = $this->loadData('User', 'default');
        $userDao = $this->User->load($usersFile[0]->getKey());
        $dir = $this->getTempDirectory().'/'.$userDao->getUserId().'/1002'; //private folder
        $identifier = $dir.'/httpupload.png';

        if (!file_exists($dir)) {
            mkdir($dir, 0700, true);
        }

        if (file_exists($identifier)) {
            unlink($identifier);
        }

        copy(BASE_PATH.'/tests/testfiles/search.png', $identifier);
        $page = 'javauploaddownload/upload/gethttpuploadoffset/?uploadUniqueIdentifier='.$userDao->getUserId().'/1002/httpupload.png&testingmode=1';
        $this->dispatchUrI($page, $userDao);
        $content = $this->getBody();

        if (strpos($content, '[OK]') === false) {
            $this->fail();
        }

        if (strpos($content, '[ERROR]') !== false) {
            $this->fail();
        }
    }

    /** Test upload controller gethttpuploaduniqueidentifier action */
    public function testGethttpuploaduniqueidentifierAction()
    {
        $this->setupDatabase(array('default'));
        $usersFile = $this->loadData('User', 'default');
        $userDao = $this->User->load($usersFile[0]->getKey());
        $identifier = $this->getTempDirectory().'/httpupload.png';

        if (file_exists($identifier)) {
            unlink($identifier);
        }

        copy(BASE_PATH.'/tests/testfiles/search.png', $identifier);
        $page = 'javauploaddownload/upload/gethttpuploaduniqueidentifier?filename=httpupload.png&testingmode=1';
        $this->dispatchUrI($page, $userDao);
        $this->assertEquals(trim($this->getBody()), '[ERROR]You must specify a parent folder or item.');
        $this->resetAll();
        $folders = $userDao->getFolder()->getFolders();
        $page .= '&parentFolderId='.$folders[0]->getKey();
        $this->dispatchUrI($page, $userDao);
        $content = $this->getBody();

        if (strpos($content, '[OK]') === false) {
            $this->fail();
        }

        if (strpos($content, '[ERROR]') !== false) {
            $this->fail();
        }
    }

    /** Test upload controller processjavaupload action */
    public function testProcessjavauploadAction()
    {
        $this->setupDatabase(array('default'));
        $usersFile = $this->loadData('User', 'default');
        $userDao = $this->User->load($usersFile[0]->getKey());
        $subdir = $userDao->getUserId().'/1002'; // private folder
        $dir = $this->getTempDirectory().'/'.$subdir;
        $fileBase = BASE_PATH.'/tests/testfiles/search.png';
        $file = $this->getTempDirectory().'/testing_file.png';
        $identifier = $dir.'/httpupload.png';

        if (file_exists($identifier)) {
            unlink($identifier);
        }

        copy($fileBase, $file);
        $ident = fopen($identifier, 'x+');
        fwrite($ident, ' ');
        fclose($ident);
        chmod($identifier, 0777);
        $params = 'testingmode=1&filename=search.png&localinput='.$file.'&length='.filesize($file).'&uploadUniqueIdentifier='.$subdir.'/httpupload.png';
        $page = 'javauploaddownload/upload/processjavaupload?'.$params;
        $this->dispatchUrI($page, $userDao, true);
        $this->resetAll();
        $page .= '&parentId=1002';
        $this->dispatchUrI($page, $userDao);
        $this->assertTrue(strpos($this->getBody(), '[ERROR]') === 0);
        $this->resetAll();
        $params = 'testingmode=1&filename=search.png&localinput='.$file.'&length='.(filesize($file) + 1).'&uploadUniqueIdentifier='.$subdir.'/httpupload.png';
        $page = 'javauploaddownload/upload/processjavaupload?'.$params.'&parentId=1002';
        $this->dispatchUrI($page, $userDao);
        $this->assertTrue(strpos($this->getBody(), '[OK]') === 0);
        $search = $this->Item->getItemsFromSearch('search.png', $userDao);

        if (empty($search)) {
            $this->fail('Unable to find item');
        }

        $this->setupDatabase(array('default'));
    }
}
