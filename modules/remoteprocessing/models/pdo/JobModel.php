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

require_once BASE_PATH.'/modules/remoteprocessing/models/base/JobModelBase.php';

/** job model */
class Remoteprocessing_JobModel extends Remoteprocessing_JobModelBase
{
    /** check ifthe policy is valid*/
  function policyCheck($job, $userDao = null, $policy = 0)
    {
    if(!$job instanceof Remoteprocessing_JobDao)
      {
      throw new Zend_Exception("Should be a job.");
      }

    $workflow = $job->getWorkflows();
    if(empty($workflow))
      {
      return false;
      }
    $workflowDomain = $workflow[0]->getDomain();
    if($workflowDomain == false)
      {
      return false;
      }

    $modelLoad = new MIDAS_ModelLoader();
    $workflowDomainModel = $modelLoad->loadModel('Workflowdomain', 'remoteprocessing');
    return $workflowDomainModel->policyCheck($workflowDomain, $userDao, $policy);
    }

  /** get jobs */
  function getBy($os, $condition, $expiration_date = false, $status = MIDAS_REMOTEPROCESSING_STATUS_WAIT)
    {
    if($expiration_date == false)
      {
      $expiration_date = date('c');
      }
    $sql = $this->database->select()
          ->setIntegrityCheck(false)
          ->where('os = ?', $os)
          ->where('status = ?', $status)
          ->where('expiration_date > ?', $expiration_date)
          ->order('job_id DESC');

    $rowset = $this->database->fetchAll($sql);
    $return = array();
    foreach($rowset as $row)
      {
      $tmpDao = $this->initDao('Job', $row, 'remoteprocessing');
      $return[] = $tmpDao;
      unset($tmpDao);
      }
    return $return;
    }

  /** get root job*/
  function getRoot($job)
    {
    if(!$job instanceof Remoteprocessing_JobDao)
      {
      throw new Zend_Exception("Should be a job.");
      }

    $tmp = $job->getParents();
    while(!empty($tmp))
      {
      $job = $tmp[0];
      $tmp = $job->getParents();
      }
    return $job;
    }

  /** get parents job */
  function getParents($job)
    {
    if(!$job instanceof Remoteprocessing_JobDao)
      {
      throw new Zend_Exception("Should be a job.");
      }
    $sql = $this->database->select()
            ->setIntegrityCheck(false)
            ->from($this->getName())
            ->join(array('w' => 'remoteprocessing_job2job'),
                        'w.parent_id = remoteprocessing_job.job_id ', array())
            ->where('w.job_id = ?', $job->getKey());
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

  /** get children job */
  function getChildren($job)
    {
    if(!$job instanceof Remoteprocessing_JobDao)
      {
      throw new Zend_Exception("Should be a job.");
      }
    $sql = $this->database->select()
            ->setIntegrityCheck(false)
            ->from($this->getName())
            ->joinUsing('remoteprocessing_job2job', 'job_id')
            ->where('remoteprocessing_job2job.parent_id = ?', $job->getKey());
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

  /** get job tree*/
  function getJobTree($job)
    {
    if(!$job instanceof Remoteprocessing_JobDao)
      {
      return array();
      }
    $job = $this->getRoot($job);
    $tree = array($job);
    return $this->_getJobTree($tree);
    }

  /** get job tree*/
  function getGraphFromTree($tree, $graph = array('nodes' => array(), 'edges' => array()), $level = 0)
    {
    foreach($tree as $key => $job)
      {
      if(!$this->_isNodeExits($graph['nodes'], $job->getKey()))
        {
        $graph['nodes'][] = array('name' => $job->getKey(), "label" => array('type' => 'plain', 'value' => $job->getName()), 'stencil' => $this->_getStencil($job), "position" => array( 80 + $key * 100, 30 + $level * 40));
        }
      foreach($job->children as $keyChild => $child)
        {
        if(!$this->_isNodeExits($graph['nodes'], $child->getKey()))
          {
          $graph['nodes'][] = array('name' => $child->getKey(), "label" => array('type' => 'plain', 'value' => $child->getName()), 'stencil' => $this->_getStencil($child), "position" => array(80 + $keyChild * 100, 30 + ($level+1) * 40));
          }
        $graph['edges'][] = array('src' => $job->getKey(), 'dst' => $child->getKey());
        }
      $graph = $this->getGraphFromTree($job->children, $graph, $level + 1);
      $graph['nodes'] = array_unique ( $graph['nodes'], SORT_REGULAR  );
      $graph['edges'] = array_unique ( $graph['edges'], SORT_REGULAR );
      }
    return $graph;
    }

  /** convert status to JSDot Stenciel*/
  private function _getStencil($job)
    {
    if($job->getReturnCode() == MIDAS_REMOTEPROCESSING_RETURN_FAILED)
      {
      return 'boxred';
      }
    if($job->getReturnCode() == MIDAS_REMOTEPROCESSING_RETURN_SUCESS)
      {
      return 'boxgreen';
      }
    return 'box';
    }

  /** check if node exists */
  private function _isNodeExits($nodes, $name)
    {
    foreach($nodes as $node)
      {
      if($name == $node['name'])
        {
        return true;
        }
      }
    return false;
    }

  /** get job tree */
  private function _getJobTree($tree)
    {
    foreach($tree as $key => $job)
      {
      $tree[$key]->children = $this->_getJobTree($job->getChildren());
      }
    return $tree;
    }

  /** get Related Items */
  function getRelatedItems($job, $type = null)
    {
    if(!$job instanceof Remoteprocessing_JobDao)
      {
      throw new Zend_Exception("Should be an item.");
      }

    $sql = $this->database->select()
          ->from('remoteprocessing_job2item')
          ->setIntegrityCheck(false)
          ->where('job_id = ?', $job->getKey())
          ->order('item_id DESC');

    if($type != null)
      {
      $sql = $this->database->select()
          ->from('remoteprocessing_job2item')
          ->setIntegrityCheck(false)
          ->where('job_id = ?', $job->getKey())
          ->where('type = ?', $type)
          ->order('item_id DESC');
      }

    $loader = new MIDAS_ModelLoader();
    $itemModel = $loader->loadModel('Item');
    $rowset = $this->database->fetchAll($sql);
    $return = array();
    foreach($rowset as $row)
      {
      $tmpDao = $itemModel->load($row['item_id']);
      if($tmpDao != false)
        {
        $tmpDao->type = $row['type'];
        $return[] = $tmpDao;
        unset($tmpDao);
        }
      }
    return $return;
    }

  /** add item relation*/
  function addItemRelation($job, $item, $type = MIDAS_REMOTEPROCESSING_RELATION_TYPE_EXECUTABLE)
    {
    if(!$job instanceof Remoteprocessing_JobDao)
      {
      throw new Zend_Exception("Should be a job.");
      }
    if(!$item instanceof ItemDao)
      {
      throw new Zend_Exception("Should be an item.");
      }
    if(!is_numeric($type))
      {
      throw new Zend_Exception("Should be a number.");
      }
    $this->database->link('items', $job, $item);

    // sql because the query is simple and it
    $data = array('type' => $type);
    $this->database->getDb()->update('remoteprocessing_job2item', $data, 'job_id = '.$job->getKey().' AND item_id = '.$item->getKey());
    }

  /** get by uuid*/
  function getByUuid($uuid)
    {
    $row = $this->database->fetchRow($this->database->select()->where('uuid = ?', $uuid));
    $dao = $this->initDao('Job', $row, 'remoteprocessing');
    return $dao;
    }

  /** add parent job*/
  function addParent($job, $parent)
    {
    if(!$job instanceof Remoteprocessing_JobDao)
      {
      throw new Zend_Exception("Should be a job.");
      }
    if(!$parent instanceof Remoteprocessing_JobDao)
      {
      throw new Zend_Exception("Should be a job.");
      }

    $data = array();
    $data[$this->_mainData['parents']['parent_column']] = $parent->getKey();
    $data[$this->_mainData['parents']['child_column']] = $job->getKey();
    $db = Zend_Registry::get('dbAdapter');

    $parentcolumn = $this->_mainData['parents']['parent_column'];
    $childcolumn = $this->_mainData['parents']['child_column'];

    // By definition a link is unique, so we should check
    $select = $db->select()->from($this->_mainData['parents']['table'], array('nrows' => 'COUNT(*)'))
                             ->where($parentcolumn."=?", $data[$this->_mainData['parents']['parent_column']])
                             ->where($childcolumn."=?", $data[$this->_mainData['parents']['child_column']]);

    $row = $db->fetchRow($select);
    if($row['nrows'] == 0)
      {
      return $db->insert($this->_mainData['parents']['table'], $data);
      }
    return false;
    }

  /** get related job */
  function getRelatedJob($item)
    {
    if(!$item instanceof ItemDao)
      {
      throw new Zend_Exception("Should be an item.");
      }

    $sql = $this->database->select()
          ->from('remoteprocessing_job2item')
          ->setIntegrityCheck(false)
          ->where('item_id = ?', $item->getKey())
          ->order('job_id DESC');

    $rowset = $this->database->fetchAll($sql);
    $return = array();
    foreach($rowset as $row)
      {
      $tmpDao = $this->load($row['job_id']);
      if($tmpDao != false)
        {
        $return[] = $tmpDao;
        unset($tmpDao);
        }
      }
    return $return;
    }

  /** get job by user */
  function getByUser($user, $limit = 10)
    {
    if(!$user instanceof UserDao)
      {
      throw new Zend_Exception("Should be an user.");
      }

    $sql = $this->database->select()
          ->from('remoteprocessing_job')
          ->setIntegrityCheck(false)
          ->where('creator_id = ?', $user->getKey())
          ->limit($limit)
          ->order('job_id DESC');

    $rowset = $this->database->fetchAll($sql);
    $return = array();
    foreach($rowset as $row)
      {
      $tmpDao = $this->load($row['job_id']);
      if($tmpDao != false)
        {
        $return[] = $tmpDao;
        unset($tmpDao);
        }
      }
    return $return;
    }
}  // end class