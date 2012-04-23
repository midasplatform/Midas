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
/** Worfklow controller*/
class Remoteprocessing_DashboardController extends Remoteprocessing_AppController
{
  public $_models = array('Item', 'Bitstream', 'ItemRevision', 'Assetstore', 'Folder', 'Group', 'Community', 'User');
  public $_components = array('Upload');
  public $_moduleComponents = array('Executable', 'Job');
  public $_moduleModels = array('Job', 'Workflow', 'Workflowdomain', 'WorkflowdomainPolicygroup', "WorkflowdomainPolicyuser");

  /** view a Worfklow */
  function workflowAction()
    {
    $worfklowId = $this->_getParam("workflowId");
    if(!isset($worfklowId) || !is_numeric($worfklowId))
      {
      throw new Zend_Exception("worfklowId  should be a number");
      }
    $workflow = $this->Remoteprocessing_Workflow->load($worfklowId);
    $this->view->header = $this->t("Worfklow: ".$workflow->getName());
    if(!$workflow)
      {
      throw new Zend_Exception("Unable to find workflow.");
      }

    if(!$this->Remoteprocessing_Workflow->policyCheck($workflow, $this->userSession->Dao, MIDAS_POLICY_READ))
      {
      throw new Zend_Exception("Permissions error.");
      }

    $this->view->isAdmin = $this->Remoteprocessing_Workflow->policyCheck($workflow, $this->userSession->Dao, MIDAS_POLICY_ADMIN);

    $date = new Zend_Date();
    $date->sub('30', Zend_Date::DAY);
    $jobs = $this->Remoteprocessing_Workflow->getJobsByDate($workflow, $date->toString('c'));
    $metrics = array();

    //ajax posts
    if($this->_request->isPost() && $this->view->isAdmin)
      {
      $this->_helper->layout->disableLayout();
      $this->_helper->viewRenderer->setNoRender();
      $name = $this->_getParam('workflowName');
      $description = $this->_getParam('workflowDescription');
      if(!empty($name))
        {
        $workflow->setName($name);
        $workflow->setDescription($description);
        $this->Remoteprocessing_Workflow->save($workflow);
        echo JsonComponent::encode(array(true, $this->t('Changes saved'), $name));
        }
      else
        {
        echo JsonComponent::encode(array(false, $this->t('Error')));
        }
      }

    $this->view->metrics = $metrics;
    $this->view->json['workflow']['metrics'] = $metrics;
    $this->view->workflow = $workflow;
    $this->view->jobs = $jobs;
    }

  /** view a domain */
  function domainAction()
    {
    $domainId = $this->_getParam("domainId");
    if(!isset($domainId) || !is_numeric($domainId))
      {
      throw new Zend_Exception($domainId."  should be a number");
      }
    $domain = $this->Remoteprocessing_Workflowdomain->load($domainId);
    $this->view->header = $this->t("Domain: ".$domain->getName());
    if(!$domain)
      {
      throw new Zend_Exception("Unable to find workflow.");
      }

    if(!$this->Remoteprocessing_Workflowdomain->policyCheck($domain, $this->userSession->Dao, MIDAS_POLICY_READ))
      {
      throw new Zend_Exception("Permissions error.");
      }

    $this->view->isAdmin = $this->Remoteprocessing_Workflowdomain->policyCheck($domain, $this->userSession->Dao, MIDAS_POLICY_ADMIN);

    $workflows = $domain->getWorkflows();
    $this->view->workflows = $workflows;
    $this->view->domain = $domain;

    //ajax posts
    if($this->_request->isPost() && $this->view->isAdmin)
      {
      $this->_helper->layout->disableLayout();
      $this->_helper->viewRenderer->setNoRender();
      $name = $this->_getParam('workflowDomainName');
      $description = $this->_getParam('workflowDomainDescription');
      if(!empty($name))
        {
        $domain->setName($name);
        $domain->setDescription($description);
        $this->Remoteprocessing_Workflowdomain->save($domain);
        echo JsonComponent::encode(array(true, $this->t('Changes saved'), $name));
        }
      else
        {
        echo JsonComponent::encode(array(false, $this->t('Error')));
        }
      }
    }

  /** ajax dialog for managing permissions */
  function sharedomainAction()
    {
    $this->disableLayout();
    $domainId = $this->_getParam("domainId");
    if(!isset($domainId) || !is_numeric($domainId))
      {
      throw new Zend_Exception($domainId."  should be a number");
      }
    $domain = $this->Remoteprocessing_Workflowdomain->load($domainId);
    if(!$domain)
      {
      throw new Zend_Exception("Unable to find workflow.");
      }
    if(!$this->Remoteprocessing_Workflowdomain->policyCheck($domain, $this->userSession->Dao, MIDAS_POLICY_ADMIN))
      {
      throw new Zend_Exception("Permissions error.");
      }

    if($this->_request->isPost())
      {
      $this->_helper->viewRenderer->setNoRender();
      $setPublic = $this->_getParam('setPublic');
      $setPrivate = $this->_getParam('setPrivate');
      $createPolicy = $this->_getParam('createPolicy');
      $removePolicy = $this->_getParam('removePolicy');
      $changePolicy = $this->_getParam('changePolicy');
      if(isset($changePolicy))
        {
        $changeVal = $this->_getParam('changeVal');
        $changeType = $this->_getParam('changeType');
        $changeId = $this->_getParam('changeId');
        if($changeType == 'group')
          {
          $changePolicy = $this->Group->load($changeId);
          }
        else
          {
          $changePolicy = $this->User->load($changeId);
          }

        if($changeType == 'group')
          {
          $policyDao = $this->Remoteprocessing_WorkflowdomainPolicygroup->getPolicy($changePolicy, $domain);
          $this->Remoteprocessing_WorkflowdomainPolicygroup->delete($policyDao);
          $policyDao->setPolicy($changeVal);
          $this->Remoteprocessing_WorkflowdomainPolicygroup->save($policyDao);
          }
        else
          {
          $policyDao = $this->Remoteprocessing_WorkflowdomainPolicyuser->getPolicy($changePolicy, $domain);
          $this->Remoteprocessing_WorkflowdomainPolicyuser->delete($policyDao);
          $policyDao->setPolicy($changeVal);
          $this->Remoteprocessing_WorkflowdomainPolicyuser->save($policyDao);
          }

        echo JsonComponent::encode(array(true, $this->t('Changes saved')));
        return;
        }
      if(isset($removePolicy))
        {
        $removeType = $this->_getParam('removeType');
        $removeId = $this->_getParam('removeId');
        if($removeType == 'group')
          {
          $removePolicy = $this->Group->load($removeId);
          }
        else
          {
          $removePolicy = $this->User->load($removeId);
          }

        if($removeType == 'group')
          {
          $policyDao = $this->Remoteprocessing_WorkflowdomainPolicygroup->getPolicy($removePolicy, $domain);
          $this->Remoteprocessing_WorkflowdomainPolicygroup->delete($policyDao);
          }
        else
          {
          $policyDao = $this->Remoteprocessing_WorkflowdomainPolicyuser->getPolicy($removePolicy, $domain);
          $this->Remoteprocessing_WorkflowdomainPolicyuser->delete($policyDao);
          }
        echo JsonComponent::encode(array(true, $this->t('Changes saved')));
        return;
        }
      if(isset($createPolicy))
        {
        $newPolicyType = $this->_getParam('newPolicyType');
        $newPolicyId = $this->_getParam('newPolicyId');
        if($newPolicyType == 'community')
          {
          $newPolicy = $this->Community->load($newPolicyId)->getMemberGroup();
          }
        elseif($newPolicyType == 'group')
          {
          $newPolicy = $this->Group->load($newPolicyId);
          }
        else
          {
          $newPolicy = $this->User->load($newPolicyId);
          }

        if($newPolicy instanceof GroupDao)
          {
          $this->Remoteprocessing_WorkflowdomainPolicygroup->createPolicy($newPolicy, $domain, MIDAS_POLICY_READ);
          }
        elseif($newPolicy instanceof UserDao)
          {
          $this->Remoteprocessing_WorkflowdomainPolicyuser->createPolicy($newPolicy, $domain, MIDAS_POLICY_READ);
          }
        else
          {
          echo JsonComponent::encode(array(false, $this->t('Error')));
          return;
          }

        echo JsonComponent::encode(array(true, $this->t('Changes saved')));
        return;
        }
      if(isset($setPublic))
        {
        $anonymousGroup = $this->Group->load(MIDAS_GROUP_ANONYMOUS_KEY);
        $this->Remoteprocessing_WorkflowdomainPolicygroup->createPolicy($anonymousGroup, $domain, MIDAS_POLICY_READ);
        echo JsonComponent::encode(array(true, $this->t('Changes saved')));
        return;
        }
      if(isset($setPrivate))
        {
        $anonymousGroup = $this->Group->load(MIDAS_GROUP_ANONYMOUS_KEY);
        $policyDao = $this->Remoteprocessing_WorkflowdomainPolicygroup->getPolicy($anonymousGroup, $domain);
        $this->Remoteprocessing_WorkflowdomainPolicygroup->delete($policyDao);
        echo JsonComponent::encode(array(true, $this->t('Changes saved')));
        return;
        }
      }

    $groupPolicies = $domain->getPolicygroup();
    $userPolicies = $domain->getPolicyuser();

    $private = true;
    foreach($groupPolicies as $key => $policy)
      {
      $group = $policy->getGroup();
      $groupPolicies[$key]->group = $group;
      $groupPolicies[$key]->communityMemberGroup = false;
      if($group->getKey() == MIDAS_GROUP_ANONYMOUS_KEY)
        {
        $private = false;
        unset($groupPolicies[$key]);
        continue;
        }
      if(strpos($group->getName(), 'Admin group of community') != false || strpos($group->getName(), 'Moderators group of community') != false)
        {
        unset($groupPolicies[$key]);
        continue;
        }
      if(strpos($group->getName(), 'Members group of community') !== false)
        {
        $groupPolicies[$key]->communityMemberGroup = true;
        continue;
        }
      }

    foreach($userPolicies as $key => $policy)
      {
      $userPolicies[$key]->user = $policy->getUser();
      }

    $this->view->groupPolicies = $groupPolicies;
    $this->view->userPolicies = $userPolicies;
    $this->view->private = $private;
    $this->view->domain = $domain;

    $this->view->jsonShare = array();
    $this->view->jsonShare['domain'] = $domain->getKey();
    $this->view->jsonShare = JsonComponent::encode($this->view->jsonShare);
    } //end dialogAction

  /** view workflow Graph */
  function workflowgraphAction()
    {
    $this->disableLayout();
    $jobId = $this->_getParam("jobId");
    if(!isset($jobId) || !is_numeric($jobId))
      {
      throw new Zend_Exception($jobId." should be a number");
      }
    $job = $this->Remoteprocessing_Job->load($jobId);
    if(!$job)
      {
      throw new Zend_Exception("Unable to find job.");
      }

    if(!$this->Remoteprocessing_Job->policyCheck($job, $this->userSession->Dao, MIDAS_POLICY_READ))
      {
      throw new Zend_Exception("Permissions error.");
      }

    $this->view->tree = $this->Remoteprocessing_Job->getJobTree($job);
    $this->view->jsonGraph = JsonComponent::encode($this->Remoteprocessing_Job->getGraphFromTree($this->view->tree));

    // trick to convert position type in json
    $this->view->jsonGraph = preg_replace('/"(-?\d+\.?\d*)"/', '$1', $this->view->jsonGraph);
    }
}//end class
