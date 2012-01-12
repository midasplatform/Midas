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

  /** get Related Items */
  function getRelatedItems($job)
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