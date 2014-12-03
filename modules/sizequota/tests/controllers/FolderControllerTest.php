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

/** Folder controller test for the sizequota module. */
class Sizequota_FolderControllerTest extends ControllerTestCase
{
    public $moduleName = 'sizequota';

    /** Setup. */
    public function setUp()
    {
        $this->setupDatabase(array('default'));
        $this->setupDatabase(array('default'), $this->moduleName);
        $this->enabledModules = array('api', $this->moduleName);
        $this->_models = array('Assetstore', 'Community', 'Setting', 'User');

        parent::setUp();
    }

    /** Test the AJAX get free space call. */
    public function testGetFreeSpace()
    {
        $usersFile = $this->loadData('User', 'default');
        $user1 = $this->User->load($usersFile[0]->getKey());
        $adminUser = $this->User->load($usersFile[2]->getKey());

        $commFile = $this->loadData('Community', 'default');
        $comm = $this->Community->load($commFile[0]->getKey());

        // Exception if no folder id is set
        $this->resetAll();
        $this->dispatchUrl('/'.$this->moduleName.'/folder/getfreespace', $adminUser);
        $resp = JsonComponent::decode($this->getBody());
        $this->assertTrue($resp['status'] == false);
        $this->assertEquals($resp['message'], 'Missing folderId parameter');

        // Exception if invalid folder id is set
        $this->resetAll();
        $this->dispatchUrl('/'.$this->moduleName.'/folder/getfreespace?folderId=-7', $adminUser);
        $resp = JsonComponent::decode($this->getBody());
        $this->assertTrue($resp['status'] == false);
        $this->assertEquals($resp['message'], 'Invalid folder');

        // Exception if no read privileges
        $this->resetAll();
        $this->dispatchUrl('/'.$this->moduleName.'/folder/getfreespace?folderId='.$user1->getFolderId(), null);
        $resp = JsonComponent::decode($this->getBody());
        $this->assertTrue($resp['status'] == false);
        $this->assertEquals($resp['message'], 'Invalid policy');

        // User with read privileges should be able to get free space
        $this->resetAll();
        $this->dispatchUrl('/'.$this->moduleName.'/folder/getfreespace?folderId='.$user1->getFolderId(), $user1);
        $resp = JsonComponent::decode($this->getBody());
        $this->assertTrue($resp['status'] == true);

        // This should also work on non-root folders
        $this->resetAll();
        $this->dispatchUrl('/'.$this->moduleName.'/folder/getfreespace?folderId=1001', $user1);
        $resp = JsonComponent::decode($this->getBody());
        $this->assertTrue($resp['status'] == true);

        // Should also work for community folders
        $this->resetAll();
        $this->Setting->setConfig('defaultcommunityquota', 12345, $this->moduleName);
        $commFolders = $comm->getFolder()->getFolders();
        $this->dispatchUrl('/'.$this->moduleName.'/folder/getfreespace?folderId='.$commFolders[0]->getKey(), $adminUser);
        $resp = JsonComponent::decode($this->getBody());
        $this->assertTrue($resp['status'] == true);
        $this->assertEquals($resp['freeSpace'], 12345);
    }
}
