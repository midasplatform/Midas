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

/** Remoteprocessing_WorkflowdomainPolicygroupModelBase */
abstract class Remoteprocessing_WorkflowdomainPolicygroupModelBase extends Remoteprocessing_AppModel
{
  /** Constructor */
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'remoteprocessing_workflowdomain_policygroup';
    $this->_daoName = 'WorkflowdomainPolicygroupDao';
    $this->_mainData = array(
          'workflowdomain_id' => array('type' => MIDAS_DATA),
          'group_id' => array('type' => MIDAS_DATA),
          'policy' => array('type' => MIDAS_DATA),
          'date' => array('type' => MIDAS_DATA),
          'domain' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Workflowdomain', 'module' => 'remoteprocessing', 'parent_column' => 'workflowdomain_id', 'child_column' => 'workflowdomain_id'),
          'group' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Group', 'parent_column' => 'group_id', 'child_column' => 'group_id')
        );
    $this->initialize(); // required
    } // end __construct()

  /** Abstract functions */
  abstract function getPolicy($group, $folder);

  /** delete */
  public function delete($dao)
    {
    parent::delete($dao);
    }//end delete

  /** create a policy
   * @return FolderpolicygroupDao*/
  public function createPolicy($group, $workflowDomain, $policy)
    {
    if(!$group instanceof GroupDao)
      {
      throw new Zend_Exception("Should be a group.");
      }
    if(!$workflowDomain instanceof Remoteprocessing_WorkflowdomainDao)
      {
      throw new Zend_Exception("Should be a workflow domain.");
      }
    if(!is_numeric($policy))
      {
      throw new Zend_Exception("Should be a number.");
      }
    if(!$group->saved && !$workflowDomain->saved)
      {
      throw new Zend_Exception("Save the daos first.");
      }
    if($this->getPolicy($group, $workflowDomain) !== false)
      {
      $this->delete($this->getPolicy($group, $workflowDomain));
      }
    $this->loadDaoClass('WorkflowdomainPolicygroupDao', 'remoteprocessing');
    $policyGroupDao = new Remoteprocessing_WorkflowdomainPolicygroupDao();
    $policyGroupDao->setGroupId($group->getGroupId());
    $policyGroupDao->setWorkflowdomainId($workflowDomain->getKey());
    $policyGroupDao->setPolicy($policy);
    $this->save($policyGroupDao);
    return $policyGroupDao;
    }
} // end class FolderpolicygroupModelBase
