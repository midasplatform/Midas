<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
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
   * Return other values that are from the same submission (same submit_time and same producer)
   */
  public function getOtherValuesFromSubmission($scalar)
    {
    $sql = $this->database->select()
                ->setIntegrityCheck(false)
                ->from(array('s' => 'tracker_scalar'))
                ->join(array('t' => 'tracker_trend'), 's.trend_id = t.trend_id')
                ->where('s.submit_time = ?', $scalar->getSubmitTime())
                ->where('t.producer_id = ?', $scalar->getTrend()->getProducerId());
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
  public function getByTrendAndTimestamp($trendId, $timestamp)
    {
    $sql = $this->database->select()
                ->setIntegrityCheck(false)
                ->where('trend_id = ?', $trendId)
                ->where('submit_time = ?', $timestamp);
    return $this->initDao('Scalar', $this->database->fetchRow($sql), $this->moduleName);
    }
}
