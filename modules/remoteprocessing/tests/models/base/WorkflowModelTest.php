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
class WorkflowModelTest extends DatabaseTestCase
  {
  /** set up tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default')); //core dataset
    $this->setupDatabase(array('default'), 'remoteprocessing'); // module dataset
    $this->enabledModules = array('remoteprocessing');
    parent::setUp();
    }

  /** test GetByUuid*/
  public function testGetByUuid()
    {
    $modelLoad = new MIDAS_ModelLoader();
    $workflowModel = $modelLoad->loadModel('Workflow', 'remoteprocessing');

    $workflow = $workflowModel->getByUuid('1234');
    $this->assertEquals(1, $workflow->getKey());
    }

  /** test getJobsByDate($workflow, $date)*/
  public function testGetJobsByDate()
    {
    $modelLoad = new MIDAS_ModelLoader();
    $workflowModel = $modelLoad->loadModel('Workflow', 'remoteprocessing');
    $workflow = $workflowModel->load(1);
    $jobs = $workflowModel->getJobsByDate($workflow, date('c'));
    $this->assertEquals(0, count($jobs));
    $jobs = $workflowModel->getJobsByDate($workflow, '2011-10-25 18:01:02');
    $this->assertEquals(1, count($jobs));
    }

  /** test addJob($workflow, $date)*/
  public function testAddJob()
    {
    $modelLoad = new MIDAS_ModelLoader();
    $workflowModel = $modelLoad->loadModel('Workflow', 'remoteprocessing');
    $jobModel = $modelLoad->loadModel('Job', 'remoteprocessing');
    $workflow = $workflowModel->load(1);
    $job = $jobModel->load(1);
    $workflowModel->addJob($job, $workflow);
    $workflows = $job->getWorkflows();
    $this->assertEquals(1, count($workflows));
    }

  /** test policyCheck($workflowDomainDao, $userDao = null, $policy = 0)*/
  public function testPolicyCheck()
    {
    $modelLoad = new MIDAS_ModelLoader();
    $workflowModel = $modelLoad->loadModel('Workflow', 'remoteprocessing');
    $userModel = $modelLoad->loadModel('User');
    $workflow = $workflowModel->load(1);
    $user = $userModel->load(1);

    $groupModel = $modelLoad->loadModel('Group');
    $policygroupModel = $modelLoad->loadModel('WorkflowdomainPolicygroup', 'remoteprocessing');
    $anonymousGroup = $groupModel->load(MIDAS_GROUP_ANONYMOUS_KEY);
    $policyDao = $policygroupModel->getPolicy($anonymousGroup, $workflow->getDomain());
    if($policyDao)
      {
      $policygroupModel->delete($policyDao);
      }

    $this->assertEquals(false, $workflowModel->policyCheck($workflow));
    $this->assertEquals(true, $workflowModel->policyCheck($workflow, $user));
    }
  }
