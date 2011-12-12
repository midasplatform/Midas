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

/** ConfigControllerTest*/
class ConfigControllerTest extends ControllerTestCase
  {
  /** set up tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default', 'adminUser'));
    $this->_models = array('User');
    $this->enabledModules = array('remoteprocessing');
    parent::setUp();
    }

  /** test config */
  public function testIndex()
    {
    $usersFile = $this->loadData('User', 'adminUser');
    $userDao = $this->User->load($usersFile[0]->getKey());

    $this->dispatchUrI('/remoteprocessing/config', $userDao);
    $this->assertQuery("input#securitykey");

    $this->resetAll();

    $this->params = array();
    $securityKey = uniqid();
    $this->params['securitykey'] = $securityKey;
    $this->params['submitConfig'] = 'true';
    $this->request->setMethod('POST');
    $this->dispatchUrI("/remoteprocessing/config", $userDao);

    if(!file_exists(BASE_PATH."/core/configs/remoteprocessing.local.ini"))
      {
      $this->fail('Unable to find config file');
      }
    $applicationConfig = parse_ini_file(BASE_PATH."/core/configs/remoteprocessing.local.ini", true);

    $this->assertEquals($securityKey,  $applicationConfig['global']['securitykey']);
    }
  }
