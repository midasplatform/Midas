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
class WorkflowdomainModelTest extends DatabaseTestCase
  {
  /** set up tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default')); //core dataset
    $this->setupDatabase(array('default'), 'remoteprocessing'); // module dataset
    $this->enabledModules = array('remoteprocessing');
    parent::setUp();
    }

  /** test getLastJobs*/
  public function testGetLastJobs()
    {
    $modelLoad = new MIDAS_ModelLoader();
    $domainModel = $modelLoad->loadModel('Workflowdomain', 'remoteprocessing');
    $domain = $domainModel->load(1);
    $jobs = $domainModel->getLastJobs($domain);
    $this->assertEquals(1, count($jobs));
    }

  /** test getByUuid()*/
  public function testGetByUuid()
    {
    $modelLoad = new MIDAS_ModelLoader();
    $domainModel = $modelLoad->loadModel('Workflowdomain', 'remoteprocessing');
    $domain = $domainModel->getByUuid('1234567');
    $this->assertEquals(1, $domain->getKey());
    }

  /** test getUserDomains()*/
  public function testGetUserDomains()
    {
    $modelLoad = new MIDAS_ModelLoader();
    $domainModel = $modelLoad->loadModel('Workflowdomain', 'remoteprocessing');
    $userModel = $modelLoad->loadModel('User');
    $user = $userModel->load(1);
    $domains = $domainModel->getUserDomains($user);
    $this->assertEquals(1, count($domains));
    $this->assertEquals(1, $domains[0]->getKey());

    $user = $userModel->load(2);
    $domains = $domainModel->getUserDomains($user);
    $this->assertEquals(1, count($domains));
    }

  /** test policyCheck($workflowDomainDao, $userDao = null, $policy = 0)*/
  public function testPolicyCheck()
    {
    $modelLoad = new MIDAS_ModelLoader();
    $domainModel = $modelLoad->loadModel('Workflowdomain', 'remoteprocessing');
    $userModel = $modelLoad->loadModel('User');
    $groupModel = $modelLoad->loadModel('Group');
    $policygroupModel = $modelLoad->loadModel('WorkflowdomainPolicygroup', 'remoteprocessing');
    $domain = $domainModel->load(1);
    $user = $userModel->load(1);

    $anonymousGroup = $groupModel->load(MIDAS_GROUP_ANONYMOUS_KEY);
    $policyDao = $policygroupModel->getPolicy($anonymousGroup, $domain);
    if($policyDao)
      {
      $policygroupModel->delete($policyDao);
      }

    $this->assertEquals(false, $domainModel->policyCheck($domain));
    $this->assertEquals(true, $domainModel->policyCheck($domain, $user));
    }
  }
