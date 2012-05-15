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

/** JobControllerTest*/
class JobControllerTest extends ControllerTestCase
  {
  /** set up tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default'));
    $this->setupDatabase(array('default'), 'remoteprocessing'); // module dataset
    $this->_models = array('User', 'Item');
    $this->enabledModules = array('scheduler', 'remoteprocessing', 'api');
    parent::setUp();
    }

  /** test manage */
  public function testManage()
    {
    $userDao = $this->User->load(1);
    $this->dispatchUrI('/remoteprocessing/job/manage', null, false);
    $this->resetAll();
    $this->dispatchUrI('/remoteprocessing/job/manage', $userDao, false);
    $this->assertQuery('table.jobTree');

    }

  /** test viewAction */
  public function testViewAction()
    {
    $userDao = $this->User->load(1);
    $modelLoad = new MIDAS_ModelLoader();
    $jobModel = $modelLoad->loadModel('Job', 'remoteprocessing');
    $job = $jobModel->load(1);
    $this->resetAll();
    $this->dispatchUrI('/remoteprocessing/job/view', null, true);
    $this->resetAll();
    $this->dispatchUrI('/remoteprocessing/job/view?jobId=10000', null, true);
    $this->resetAll();
    $this->dispatchUrI('/remoteprocessing/job/view?jobId=1', null, true);
    $this->resetAll();
    $this->dispatchUrI('/remoteprocessing/job/view?jobId=1', $userDao, false);
    $this->assertQueryContentContains('body', 'Job Status');
    }


  /** test init*/
  /*public function testInit()
    {
    $usersFile = $this->loadData('User', 'adminUser');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $itemFile = $this->loadData('Item', 'default');

    $this->resetAll();
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
    }*/
  }
