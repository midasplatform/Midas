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

require_once BASE_PATH.'/modules/remoteprocessing/models/base/WorkflowModelBase.php';

/** job model */
class Remoteprocessing_WorkflowModel extends Remoteprocessing_WorkflowModelBase
{
  /** get by uuid*/
  function getByUuid($uuid)
    {
    $sql = $this->database->select()->where('uuid = ?', $uuid);
    $row = $this->database->fetchRow($sql);
    $dao = $this->initDao('Workflow', $row, 'remoteprocessing');
    return $dao;
    }

  /** check ifthe policy is valid*/
  function policyCheck($workflow, $userDao = null, $policy = 0)
    {
    if(!$workflow instanceof Remoteprocessing_WorkflowDao)
      {
      throw new Zend_Exception("Should be a workflow.");
      }

    $workflowDomain = $workflow->getDomain();
    if($workflowDomain == false)
      {
      return false;
      }

    $modelLoad = new MIDAS_ModelLoader();
    $workflowDomainModel = $modelLoad->loadModel('Workflowdomain', 'remoteprocessing');
    return $workflowDomainModel->policyCheck($workflowDomain, $userDao, $policy);
    }

  /** get job by user */
  function getJobsByDate($workflow, $date)
    {
    if(!$workflow instanceof Remoteprocessing_WorkflowDao)
      {
      throw new Zend_Exception("Should be a workflow.");
      }
    $sql = $this->database->select()
          ->from(array('j' => 'remoteprocessing_job'))
          ->join(array('w' => 'remoteprocessing_workflow2job'),
                        'w.job_id = j.job_id AND w.workflow_id = '.$workflow->getKey(), array())
          ->setIntegrityCheck(false)
          ->where('j.start_date > ?', $date)
          ->order('j.start_date DESC');
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

  /** add parent job*/
  function addJob($job, $workflow)
    {
    if(!$job instanceof Remoteprocessing_JobDao)
      {
      throw new Zend_Exception("Should be a job.");
      }
    if(!$workflow instanceof Remoteprocessing_WorkflowDao)
      {
      throw new Zend_Exception("Should be a workflow.");
      }

    $data = array();
    $data[$this->_mainData['jobs']['parent_column']] = $workflow->getKey();
    $data[$this->_mainData['jobs']['child_column']] = $job->getKey();
    $db = Zend_Registry::get('dbAdapter');

    $parentcolumn = $this->_mainData['jobs']['parent_column'];
    $childcolumn = $this->_mainData['jobs']['child_column'];

    // By definition a link is unique, so we should check
    $select = $db->select()->from($this->_mainData['jobs']['table'], array('nrows' => 'COUNT(*)'))
                             ->where($parentcolumn."=?", $data[$this->_mainData['jobs']['parent_column']])
                             ->where($childcolumn."=?", $data[$this->_mainData['jobs']['child_column']]);

    $row = $db->fetchRow($select);
    if($row['nrows'] == 0)
      {
      return $db->insert($this->_mainData['jobs']['table'], $data);
      }
    return false;
    }
}  // end class