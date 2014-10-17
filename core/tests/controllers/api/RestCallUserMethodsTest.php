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

require_once BASE_PATH.'/core/tests/controllers/api/RestCallMethodsTest.php';

/** Tests the functionality of the web API User methods */
class RestCallUserMethodsTest extends RestCallMethodsTest
{
    /** set up tests */
    public function setUp()
    {
        parent::setUp();
    }

    /** Get the folders corresponding to the user */
    public function testUserFolders()
    {
        // Try anonymously first
        $apipath = '/user/folders';
        $resp = $this->_callRestApi('GET', $apipath);
        $this->_assertStatusOk($resp);
        // No user folders should be visible anonymously
        $this->assertEquals(count($resp['body']->data), 0);

        $this->resetAll();
        $this->params['token'] = $this->_loginAsNormalUser();
        $resp = $this->_callRestApi('GET', $apipath);
        $this->_assertStatusOk($resp);
        $this->assertEquals(count($resp['body']->data), 2);

        // We do not expect folder 1000 to be returned, as this is an internal-only
        // value not intended to be exposed by the web api
        foreach ($resp['body']->data as $folder) {
            $this->assertEquals($folder->_model, 'Folder');
            $this->assertEquals($folder->parent_id, 1000);
        }
        $this->assertEquals($resp['body']->data[0]->name, 'User 1 name Folder 2');
        $this->assertEquals($resp['body']->data[1]->name, 'User 1 name Folder 3');
    }

    /** Test get user's default API key using username and password */
    public function testUserApikeyDefault()
    {
        $usersFile = $this->loadData('User', 'default');
        $userDao = $this->User->load($usersFile[0]->getKey());

        // Expected API key
        $userApiModel = MidasLoader::loadModel('Userapi');
        $userApiModel->createDefaultApiKey($userDao);
        $apiKey = $userApiModel->getByAppAndUser('Default', $userDao)->getApikey();

        $this->resetAll();

        $this->User->changePassword($userDao, 'test');
        $this->User->save($userDao);

        $apipath = '/system/defaultapikey';
        $this->params['email'] = $userDao->getEmail();
        $this->params['password'] = 'test';
        $resp = $this->_callRestApi('POST', $apipath);
        $this->_assertStatusOk($resp);

        $this->assertEquals($resp['body']->data->apikey, $apiKey);
    }

    /** Test that we can authenticate to the web API using the user session */
    public function testSessionAuthentication()
    {
        $usersFile = $this->loadData('User', 'default');
        $userDao = $this->User->load($usersFile[0]->getKey());

        $this->resetAll();
        $this->params = array();
        $apipath = '/user/folders';
        $this->params['useSession'] = 'true';
        $resp = $this->_callRestApi('GET', $apipath, $userDao);
        $this->_assertStatusOk($resp);

        // We should see the user's folders
        $this->assertEquals(count($resp['body']->data), 2);

        foreach ($resp['body']->data as $folder) {
            $this->assertEquals($folder->_model, 'Folder');
            $this->assertEquals($folder->parent_id, 1000);
        }
        $this->assertEquals($resp['body']->data[0]->name, 'User 1 name Folder 2');
        $this->assertEquals($resp['body']->data[1]->name, 'User 1 name Folder 3');
    }

    /** Test getting user by id and email and first name + last name */
    public function testUserGet()
    {
        // Test getting a user by id
        $this->params['token'] = $this->_loginAsNormalUser();
        $apipath = '/user/1';
        $resp = $this->_callRestApi('GET', $apipath);
        $this->_assertStatusOk($resp);
        $this->assertEquals($resp['body']->data->firstname, 'FirstName1');
        $this->assertEquals($resp['body']->data->lastname, 'LastName1');

        // Test getting a user by email
        $this->resetAll();
        $apipath = '/user/search';
        $this->params['token'] = $this->_loginAsNormalUser();
        $this->params['email'] = 'user1@user1.com';
        $resp = $this->_callRestApi('GET', $apipath);
        $this->_assertStatusOk($resp);
        $this->assertEquals($resp['body']->data->email, 'user1@user1.com');

        // Test getting a user by first name and last name
        $this->resetAll();
        $apipath = '/user/search';
        $this->params['token'] = $this->_loginAsNormalUser();
        $this->params['firstname'] = 'FirstName2';
        $this->params['lastname'] = 'LastName2';
        $resp = $this->_callRestApi('GET', $apipath);
        $this->_assertStatusOk($resp);
        $this->assertEquals($resp['body']->data->user_id, '2');
    }

    /** Test listing the users */
    public function testUserList()
    {
        $apipath = '/user';
        $this->params['token'] = $this->_loginAsNormalUser();
        $this->params['limit'] = '20';
        $resp = $this->_callRestApi('GET', $apipath);
        $this->_assertStatusOk($resp);
        $usersFile = $this->loadData('User', 'default');
        $this->assertEquals(count($resp['body']->data), count($usersFile));
    }
}
