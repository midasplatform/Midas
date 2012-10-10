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
require_once BASE_PATH.'/modules/tracker/models/base/TrendModelBase.php';

/**
 * Trend PDO Model
 */
class Tracker_TrendModel extends Tracker_TrendModelBase
{
  /**
   * Return the matching trend dao if it exists
   */
  public function getMatch($producerId, $metricName, $configItemId, $testDatasetId, $truthDatasetId)
    {
    $sql = $this->database->select()
                          ->setIntegrityCheck(false)
                          ->where('producer_id = ?', $producerId)
                          ->where('metric_name = ?', $metricName);
    if($configItemId == null)
      {
      $sql->where('config_item_id IS NULL');
      }
    else
      {
      $sql->where('config_item_id = ?', $configItemId);
      }

    if($truthDatasetId == null)
      {
      $sql->where('truth_dataset_id IS NULL');
      }
    else
      {
      $sql->where('truth_dataset_id = ?', $truthDatasetId);
      }

    if($testDatasetId == null)
      {
      $sql->where('test_dataset_id IS NULL');
      }
    else
      {
      $sql->where('test_datasetid = ?', $testDatasetId);
      }
    return $this->initDao('Trend', $this->database->fetchRow($sql), $this->moduleName);
    }
}
