<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
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
