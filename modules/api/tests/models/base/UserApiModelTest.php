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
/** test hello model*/
class UserApiModelTest extends DatabaseTestCase
  {
  /** set up tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default')); //core dataset
    $this->setupDatabase(array('default'), 'api'); // module dataset
    $this->enabledModules = array('api');
    parent::setUp();
    }

  /** Test that UserapiModel::createKey works */
  public function testGenerateApiKeyModel()
    {
    $modelLoad = new MIDAS_ModelLoader();
    $userApiModel = $modelLoad->loadModel('Userapi', 'api');

    $apiKey = $userApiModel->createKeyFromEmailPassword('Test App', 'user1@user1.com', 'test');
    $this->assertTrue($apiKey instanceof Api_UserapiDao);
    $this->assertEquals(strlen($apiKey->getApikey()), 40);
    }

  /** Test that UserapiModel::createDefaultApiKey works */
  public function testDefaultApiKeyModel()
    {
    $modelLoad = new MIDAS_ModelLoader();
    $userApiModel = $modelLoad->loadModel('Userapi', 'api');

    $userDao = new UserDao();
    $userDao->setUserId(1);
    $userDao->setEmail('user1@user1.com');
    $userDao->setPassword('35fd8ba86ba403ffcc00feac5355ad20');
    $userApiModel->createDefaultApiKey($userDao);

    $dao = $userApiModel->getByAppAndEmail('Default', 'user1@user1.com');
    $this->assertTrue($dao instanceof Api_UserapiDao);
    $this->assertEquals(md5('user1@user1.com35fd8ba86ba403ffcc00feac5355ad20Default'), $dao->getApikey());
    }
  }
