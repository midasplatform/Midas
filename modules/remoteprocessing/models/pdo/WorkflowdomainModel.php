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

require_once BASE_PATH.'/modules/remoteprocessing/models/base/WorkflowdomainModelBase.php';

/** job model */
class Remoteprocessing_WorkflowdomainModel extends Remoteprocessing_WorkflowdomainModelBase
{
  /** get last jobs*/
  function getLastJobs($domain, $limit = 20)
    {
    if(!$domain instanceof Remoteprocessing_WorkflowdomainDao)
      {
      throw new Zend_Exception("Should be a domain.");
      }
    $workflows = $domain->getWorkflows();
    if(empty($workflows))
      {
      return array();
      }
    $workflowsId = array();
    foreach($workflows as $w)
      {
      $workflowsId[] = $w->getKey();
      }
    $sql = $this->database->select()
          ->from(array('j' => 'remoteprocessing_job'))
          ->join(array('w' => 'remoteprocessing_workflow2job'),
                        'w.job_id = j.job_id', array())
          ->setIntegrityCheck(false)
          ->where(' w.workflow_id IN (?)', $workflowsId)
          ->order('j.start_date DESC')
          ->limit($limit);
    $rowset = $this->database->fetchAll($sql);
    $return = array();
    foreach($rowset as $row)
      {
      $tmpDao = $this->initDao('Job', $row, 'remoteprocessing');
      if($tmpDao != false)
        {
        $return[] = $tmpDao;
        unset($tmpDao);
        }
      }
    return $return;
    }

  /** get by uuid*/
  function getByUuid($uuid)
    {
    $sql = $this->database->select()->where('uuid = ?', $uuid);
    $row = $this->database->fetchRow($sql);
    $dao = $this->initDao('Workflowdomain', $row, 'remoteprocessing');
    return $dao;
    }

  /** get users domains */
  function getUserDomains($userDao, $policy = MIDAS_POLICY_ADMIN)
    {
    $sql = $this->database->select()
            ->setIntegrityCheck(false)
            ->distinct()
            ->from(array('p' => 'remoteprocessing_workflowdomain_policyuser'),
                    array())
            ->where('policy >= ?', $policy)
            ->where('user_id = ? ', $userDao->getKey());

    $rowset = $this->database->fetchAll($sql);

    $return = array();
    foreach($rowset as $row)
      {
      $tmpDao = $this->initDao('Workflowdomain', $row, 'remoteprocessing');
      $return[] = $tmpDao;
      unset($tmpDao);
      }
    if(empty($return))
      {
      $this->loadDaoClass('WorkflowdomainDao', 'remoteprocessing');
      $dao = new Remoteprocessing_WorkflowdomainDao();
      $dao->setName('Default');
      $this->save($dao);

      $modelLoad = new MIDAS_ModelLoader();
      $policy = $modelLoad->loadModel('WorkflowdomainPolicyuser', 'remoteprocessing');
      $policy->createPolicy($userDao, $dao, MIDAS_POLICY_ADMIN);
      $return[] = $dao;
      }
    return $return;
    }

  /** check ifthe policy is valid*/
  function policyCheck($workflowDomainDao, $userDao = null, $policy = 0)
    {
    if(!$workflowDomainDao instanceof Remoteprocessing_WorkflowdomainDao || !is_numeric($policy))
      {
      throw new Zend_Exception("Error param.");
      }
    if($userDao == null)
      {
      $userId = -1;
      }
    else if(!$userDao instanceof UserDao)
      {
      throw new Zend_Exception("Should be an user.");
      }
    else
      {
      $userId = $userDao->getUserId();
      if($userDao->isAdmin())
        {
        return true;
        }
      }

    $subqueryUser = $this->database->select()
                          ->setIntegrityCheck(false)
                          ->from(array('p' => 'remoteprocessing_workflowdomain_policyuser'),
                                 array('workflowdomain_id'))
                          ->where('policy >= ?', $policy)
                          ->where('p.workflowdomain_id = ?', $workflowDomainDao->getKey())
                          ->where('user_id = ? ', $userId);

    $subqueryGroup = $this->database->select()
                    ->setIntegrityCheck(false)
                    ->from(array('p' => 'remoteprocessing_workflowdomain_policygroup'),
                           array('workflowdomain_id'))
                    ->where('policy >= ?', $policy)
                    ->where('p.workflowdomain_id = ?', $workflowDomainDao->getKey())
                    ->where('( '.$this->database->getDB()->quoteInto('group_id = ? ', MIDAS_GROUP_ANONYMOUS_KEY).' OR
                              group_id IN (' .new Zend_Db_Expr(
                              $this->database->select()
                                   ->setIntegrityCheck(false)
                                   ->from(array('u2g' => 'user2group'),
                                          array('group_id'))
                                   ->where('u2g.user_id = ?', $userId)
                                   .'))' ));

    $sql = $this->database->select()
            ->union(array($subqueryUser, $subqueryGroup));
    $row = $this->database->fetchRow($sql);
    if($row == null)
      {
      return false;
      }
    return true;
    }//end policyCheck
}  // end class