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

/** ExecutableControllerTest */
class DashboardControllerTest extends ControllerTestCase
  {
  /** set up tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default'));
    $this->setupDatabase(array('default'), 'remoteprocessing'); // module dataset
    $this->_models = array('User', 'Item', 'ItemRevision');
    $this->enabledModules = array('remoteprocessing');
    parent::setUp();
    }

  /** test workflowAction */
  public function testWorkflowAction()
    {
    $modelLoad = new MIDAS_ModelLoader();
    $workflowModel = $modelLoad->loadModel('Workflow', 'remoteprocessing');
    $workflow = $workflowModel->load(1);
    $userDao = $this->User->load(1);
    $this->dispatchUrI('/remoteprocessing/dashboard/workflow', $userDao, true);
    $this->resetAll();
    $this->params = array('workflowId' => '10000');
    $this->dispatchUrI('/remoteprocessing/dashboard/workflow', $userDao, true);
    $this->resetAll();
    $this->params = array('workflowId' => '1');
    $this->dispatchUrI('/remoteprocessing/dashboard/workflow', null, true);
    $this->resetAll();
    $this->params = array('workflowId' => '1');
    $this->dispatchUrI('/remoteprocessing/dashboard/workflow', $userDao, false);
    $this->assertQueryContentContains('body', $workflow->getName());
    $this->resetAll();

    $this->params = array('workflowId' => '1', 'workflowName' => 'testNamePost', 'workflowDescription' => 'testDescriptionPost');
    $this->getRequest()->setMethod('POST');
    $this->dispatchUrI('/remoteprocessing/dashboard/workflow', $userDao, false);
    $this->resetAll();

    $this->params = array('workflowId' => '1');
    $this->dispatchUrI('/remoteprocessing/dashboard/workflow', $userDao, false);
    $this->assertQueryContentContains('body', 'testNamePost');
    $this->assertQueryContentContains('body', 'testDescriptionPost');
    }

  /** test domainAction */
  public function testDomainAction()
    {
    $modelLoad = new MIDAS_ModelLoader();
    $domainModel = $modelLoad->loadModel('Workflowdomain', 'remoteprocessing');
    $domain = $domainModel->load(1);
    $userDao = $this->User->load(1);
    $this->dispatchUrI('/remoteprocessing/dashboard/domain', $userDao, true);
    $this->resetAll();
    $this->params = array('domainId' => '10000');
    $this->dispatchUrI('/remoteprocessing/dashboard/domain', $userDao, true);
    $this->resetAll();
    $this->params = array('domainId' => '1');
    $this->dispatchUrI('/remoteprocessing/dashboard/domain', null, true);
    $this->resetAll();
    $this->params = array('domainId' => '1');
    $this->dispatchUrI('/remoteprocessing/dashboard/domain', $userDao, false);
    $this->assertQueryContentContains('body', $domain->getName());
    $this->resetAll();

    $this->params = array('domainId' => '1', 'workflowDomainName' => 'testNamePost', 'workflowDomainDescription' => 'testDescriptionPost');
    $this->getRequest()->setMethod('POST');
    $this->dispatchUrI('/remoteprocessing/dashboard/domain', $userDao, false);
    $this->resetAll();

    $this->params = array('domainId' => '1');
    $this->dispatchUrI('/remoteprocessing/dashboard/domain', $userDao, false);
    $this->assertQueryContentContains('body', 'testNamePost');
    $this->assertQueryContentContains('body', 'testDescriptionPost');
    }

  /** Test sharedomainAction */
  public function testSharedomainAction()
    {
    $user1 = $this->User->load(1);
    $user2 = $this->User->load(2);
    $modelLoad = new MIDAS_ModelLoader();
    $domainModel = $modelLoad->loadModel('Workflowdomain', 'remoteprocessing');
    $domain = $domainModel->load(1);

    // should throw an exception due to missing parameters
    $this->dispatchUrI('/remoteprocessing/dashboard/sharedomain', null, true);

    // should throw an exception due to invalid element id
    $this->resetAll();
    $this->dispatchUrI('/remoteprocessing/dashboard/sharedomain?domainId=834', null, true);

    // should throw an exception due to invalid permissions
    $this->resetAll();
    $this->dispatchUrI('/remoteprocessing/dashboard/sharedomain?domainId=1', null, true);

    // should throw an exception due to invalid permissions (write permissions)
    $this->resetAll();
    $this->dispatchUrI('/remoteprocessing/dashboard/sharedomain?domainId=1', $user2, true);

    // should render the dialog since the user has admin privileges
    $this->resetAll();
    $this->dispatchUrI('/remoteprocessing/dashboard/sharedomain?domainId=1', $user1);
    $this->assertController('dashboard');
    $this->assertQuery('div#permissionPrivate');
    $this->assertQuery('td.changePermissionSelectBox');
    $this->assertQueryContentContains('div.jsonShareContent', json_encode(array('domain' => '1')));

    // now create a new privilege entry for user2
    $this->resetAll();
    $this->request->setMethod('POST');
    $url = '/remoteprocessing/dashboard/sharedomain?domainId=1&createPolicy';
    $url .= '&newPolicyId='.$user2->getKey();
    $this->dispatchUrI($url, $user1);

    // user 2 should now have read privileges, but not any higher
    $this->assertTrue($domainModel->policyCheck($domain, $user2, MIDAS_POLICY_READ));
    $this->assertFalse($domainModel->policyCheck($domain, $user2, MIDAS_POLICY_WRITE));

    // now remove permissions for user 2
    $this->resetAll();
    $this->request->setMethod('POST');
    $url = '/remoteprocessing/dashboard/sharedomain?domainId=1&removePolicy&removeType=user';
    $url .= '&removeId='.$user2->getKey();
    $this->dispatchUrI($url, $user1);

    // user 2 should no longer have any permissions
    $this->assertFalse($domainModel->policyCheck($domain, $user2, MIDAS_POLICY_READ));
    $this->assertFalse($domainModel->policyCheck($domain, $user2, MIDAS_POLICY_WRITE));
    $this->assertFalse($domainModel->policyCheck($domain, $user2, MIDAS_POLICY_ADMIN));

    // set the permissions to public
    $this->resetAll();
    $this->request->setMethod('POST');
    $url = '/remoteprocessing/dashboard/sharedomain?domainId=1&setPublic';
    $this->dispatchUrI($url, $user1);

    // user 2 should now have read privileges, but no higher
    $this->assertTrue($domainModel->policyCheck($domain, $user2, MIDAS_POLICY_READ));
    $this->assertFalse($domainModel->policyCheck($domain, $user2, MIDAS_POLICY_WRITE));
    }
  }
