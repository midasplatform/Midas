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

/** JobControllerTest*/
class JobControllerTest extends ControllerTestCase
  {
  /** set up tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default', 'adminUser'));
    $this->_models = array('User', 'Item');
    $this->enabledModules = array('scheduler', 'remoteprocessing', 'api');
    parent::setUp();
    }

  /** test manage */
  public function testManage()
    {
    $usersFile = $this->loadData('User', 'adminUser');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $itemFile = $this->loadData('Item', 'default');

    $revision = $this->Item->getLastRevision($itemFile[0]);

    $this->dispatchUrI('/remoteprocessing/job/manage?itemId='.$itemFile[0]->getKey(), null, true);
    $this->dispatchUrI('/remoteprocessing/job/manage?itemId='.$itemFile[0]->getKey(), $userDao, false);

    // means job found
    $this->assertQuery('table#tableJobsList');
    }

  /** test init*/
  public function testInit()
    {
    $usersFile = $this->loadData('User', 'adminUser');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $itemFile = $this->loadData('Item', 'default');

    $this->resetAll();
    $revision = $this->Item->getLastRevision($itemFile[0]);
    $this->dispatchUrI('/remoteprocessing/job/init?itemId='.$itemFile[0]->getKey(), $userDao, false);

    // page empty because there is a redirection
    $this->assertEquals($this->getBody(), '');

    // create definition file
    $this->resetAll();
    $this->params = array();
    $this->params['results'][0] = 'foo;foo;foo;foo;foo;foo';
    $this->request->setMethod('POST');
    $this->dispatchUrI("/remoteprocessing/executable/define?itemId=".$itemFile[0]->getKey(), $userDao);

    $this->resetAll();
    $this->dispatchUrI('/remoteprocessing/job/init?itemId='.$itemFile[0]->getKey(), $userDao, false);

    $this->assertQuery('#creatJobLink');
    }
  }
