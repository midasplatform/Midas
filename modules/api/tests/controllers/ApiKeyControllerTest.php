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

/** Tests the functionality of the user's API keys using the controllers */
class ApiKeyControllerTest extends ControllerTestCase
  {
  /** set up tests */
  public function setUp()
    {
    $this->setupDatabase(array('default')); //core dataset
    $this->setupDatabase(array('default'), 'api'); // module dataset
    $this->enabledModules = array('api');
    $this->_models = array('User');
    $this->_daos = array('User');
    parent::setUp();
    }

  /** Make sure changing a password changes the default api key */
  public function testChangePasswordChangesDefaultApiKey()
    {
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());

    $modelLoad = new MIDAS_ModelLoader();
    $userApiModel = $modelLoad->loadModel('Userapi', 'api');
    $userApiModel->createDefaultApiKey($userDao);
    $preKey = $userApiModel->getByAppAndUser('Default', $userDao)->getApikey();
    $this->assertEquals(strlen($preKey), 32);

    $this->params['oldPassword'] = 'test';
    $this->params['newPassword'] = 'test1';
    $this->params['newPasswordConfirmation'] = 'test1';
    $this->params['modifyPassword'] = 'modifyPassword';
    $this->request->setMethod('POST');

    $page = $this->webroot.'user/settings';
    $this->dispatchUrI($page, $userDao);

    $postKey = $userApiModel->getByAppAndUser('Default', $userDao)->getApikey();
    $this->assertNotEquals($preKey, $postKey);
    $passwordPrefix = Zend_Registry::get('configGlobal')->password->prefix;
    $this->assertEquals($postKey, md5($userDao->getEmail().md5($passwordPrefix.'test1').'Default'));
    }

  /** Make sure adding a new user adds a default api key */
  public function testNewUserGetsDefaultApiKey()
    {
    // Register a new user
    $this->params['email'] = 'some.user@server.com';
    $this->params['password1'] = 'midas';
    $this->params['password2'] = 'midas';
    $this->params['firstname'] = 'some';
    $this->params['lastname'] = 'user';
    $this->request->setMethod('POST');

    $page = $this->webroot.'user/register';
    $this->dispatchUrI($page);

    // Check that their default api key was created
    $modelLoad = new MIDAS_ModelLoader();
    $userApiModel = $modelLoad->loadModel('Userapi', 'api');
    $key = $userApiModel->getByAppAndEmail('Default', 'some.user@server.com')->getApikey();
    $passwordPrefix = Zend_Registry::get('configGlobal')->password->prefix;
    $this->assertEquals($key, md5('some.user@server.com'.md5($passwordPrefix.'midas').'Default'));
    }

  /**
   * Make sure that existing users get a default api key
   * created for them when the web api module is installed
   */
  public function testExistingUsersGetDefaultKeysOnInstall()
    {
    $modelLoader = new MIDAS_ModelLoader();
    $userApiModel = $modelLoader->loadModel('Userapi', 'api');
    $userApiDao = $userApiModel->getByAppAndEmail('Default', 'user1@user1.com');

    $this->assertTrue($userApiDao == false, 'Key should not exist before install');
    $componentLoader = new MIDAS_ComponentLoader();
    $utilityComponent = $componentLoader->loadComponent('Utility');
    $utilityComponent->installModule('api');

    $userApiDao = $userApiModel->getByAppAndEmail('Default', 'user1@user1.com');

    $this->assertTrue($userApiDao != false, 'Api key was not created for existing user');
    $this->assertEquals($userApiDao->getApikey(), md5('user1@user1.com35fd8ba86ba403ffcc00feac5355ad20Default'));
    }
  }
