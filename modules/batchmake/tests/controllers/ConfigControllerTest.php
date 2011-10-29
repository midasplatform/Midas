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

// need to include the module constant for this test
require_once BASE_PATH.'/modules/batchmake/constant/module.php';
require_once BASE_PATH.'/modules/batchmake/tests/controllers/BatchmakeControllerTest.php';

/** config controller tests*/
class ConfigControllerTest extends BatchmakeControllerTest
  {



  /** set up tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default'));
    $this->_daos = array('User');
    $this->_models = array('User');
    $this->enabledModules = array('batchmake');
    parent::setUp();
    }




  /** test index action*/
  public function testIndexAction()
    {
    // first try to bring up the page without logging in, should get an exception
    $usersFile = $this->loadData('User', 'default');
    $nullUserDao = null;
    foreach($usersFile as $userDao)
      {
      if($userDao->getFirstname() === 'Admin')
        {
        $adminUserDao = $userDao;
        }
      else if($userDao->getFirstname() === 'FirstName1')
        {
        $nonAdminUserDao = $userDao;
        }
      }

    $withException = true;
    $page = '/batchmake/config/index';
    $this->params = array();
    $this->getRequest()->setMethod('GET');
    $this->dispatchUrI($page, $nullUserDao, $withException);

    // now login with a non-admin account, should get an exception
    $this->resetAll();
    $this->params = array();
    $this->getRequest()->setMethod('GET');
    $this->dispatchUrI($page, $nonAdminUserDao, $withException);

    // now login with an admin account
    $this->resetAll();
    $this->params = array();
    $this->getRequest()->setMethod('GET');
    $this->dispatchUrI($page, $adminUserDao);

    $body = $this->getBody();

    $this->assertModule("batchmake");
    $this->assertController('config');
    $this->assertAction("index");
    if(strpos($body, "Batchmake Configuration") === false)
      {
      $this->fail('Unable to find body element');
      }

    $this->assertQuery("form#configForm");
    }


  }
