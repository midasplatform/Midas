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

require_once BASE_PATH.'/modules/tracker/models/base/ScalarModelBase.php';

/**
 * Scalar PDO Model
 */
class Tracker_ScalarModel extends Tracker_ScalarModelBase
  {
  /**
   * Associate an item with a particular scalar
   * @param scalar The scalar dao
   * @param item The item dao
   * @param label The association label
   */
  public function associateItem($scalar, $item, $label)
    {
    $data = array(
      'scalar_id' => $scalar->getKey(),
      'item_id' => $item->getKey(),
      'label' => $label);
    Zend_Registry::get('dbAdapter')->insert('tracker_scalar2item', $data);
    }

  /**
   * Return all items associated with this scalar, and their corresponding labels
   */
  public function getAssociatedItems($scalar)
    {
    $sql = $this->database->select()
                ->setIntegrityCheck(false)
                ->from('tracker_scalar2item')
                ->where('scalar_id = ?', $scalar->getKey());
    $rows = $this->database->fetchAll($sql);
    $results = array();
    $itemModel = MidasLoader::loadModel('Item');
    foreach($rows as $row)
      {
      $item = $itemModel->load($row['item_id']);
      $results[] = array('label' => $row['label'], 'item' => $item);
      }
    return $results;
    }

  /**
   * Return other values that are from the same submission (same submit_time, producer, and user id)
   */
  public function getOtherValuesFromSubmission($scalar)
    {
    $sql = $this->database->select()
                ->setIntegrityCheck(false)
                ->from(array('s' => 'tracker_scalar'))
                ->join(array('t' => 'tracker_trend'), 's.trend_id = t.trend_id')
                ->where('s.submit_time = ?', $scalar->getSubmitTime())
                ->where('s.user_id = ?', $scalar->getUserId())
                ->where('t.producer_id = ?', $scalar->getTrend()->getProducerId())
                ->order('metric_name ASC');
    $rows = $this->database->fetchAll($sql);
    $scalars = array();
    foreach($rows as $row)
      {
      $scalars[$row['metric_name']] = $row['value'].' '.$row['unit'];
      }
    return $scalars;
    }

  /**
   * Delete the scalar (deletes all result item associations as well)
   */
  public function delete($scalar)
    {
    Zend_Registry::get('dbAdapter')->delete('tracker_scalar2item', 'scalar_id = '.$scalar->getKey());
    parent::delete($scalar);
    }

  /**
   * Get a scalar dao based on trend and timestamp
   */
  public function getByTrendAndTimestamp($trendId, $timestamp, $userId = null)
    {
    $sql = $this->database->select()
                ->setIntegrityCheck(false)
                ->where('trend_id = ?', $trendId)
                ->where('submit_time = ?', $timestamp);
    if($userId !== null)
      {
      $sql->where('user_id = ?', $userId);
      }
    return $this->initDao('Scalar', $this->database->fetchRow($sql), $this->moduleName);
    }

  public function getDistinctBranches()
    {
    $sql = $this->database->select()
                ->setIntegrityCheck(false)
                ->from(array('s' => 'tracker_scalar'), 'branch')
                ->distinct();
    $rows = $this->database->fetchAll($sql);
    $branches = array();
    foreach($rows as $row)
      {
      $branches[] = $row['branch'];
      }
    return $branches;
    }
  }
