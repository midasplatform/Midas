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
