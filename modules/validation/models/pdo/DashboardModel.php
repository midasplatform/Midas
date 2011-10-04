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
require_once BASE_PATH.'/modules/validation/models/base/DashboardModelBase.php';

/**
 * Dashboard PDO Model
 */
class Validation_DashboardModel extends Validation_DashboardModelBase
{
  /**
   * Return all the record in the table
   * @return Array of ValidationDao
   */
  function getAll()
    {
    $sql = $this->database->select();
    $rowset = $this->database->fetchAll($sql);
    $rowsetAnalysed = array();
    foreach($rowset as $keyRow => $row)
      {
      $tmpDao = $this->initDao('Dashboard', $row, 'validation');
      $rowsetAnalysed[] = $tmpDao;
      }
    return $rowsetAnalysed;
    }

  /**
   * Add a results folder to the dashboard
   * @return void
   */
  function addResult($dashboard, $folder)
    {
    if(!$dashboard instanceof Validation_DashboardDao)
      {
      throw new Zend_Exception("Should be a dasboard.");
      }
    if(!$folder instanceof FolderDao)
      {
      throw new Zend_Exception("Should be a folder.");
      }
    $this->database->link('results', $dashboard, $folder);
    }

  /**
   * remove a results folder from the dashboard
   * @return void
   */
  function removeResult($dashboard, $folder)
    {
    if(!$dashboard instanceof Validation_DashboardDao)
      {
      throw new Zend_Exception("Should be a dashboard.");
      }
    if(!$folder instanceof FolderDao)
      {
      throw new Zend_Exception("Should be a folder.");
      }
    $sql = $this->database->select()
      ->setIntegrityCheck(false)
      ->from(array('d' => 'validation_dashboard'))
      ->join(array('j' => 'validation_dashboard2scalarresult'),
             'd.dashboard_id = j.dashboard_id')
      ->join(array('r' => 'validation_scalarresult'),
             'j.scalarresult_id = r.scalarresult_id')
      ->where('r.folder_id = '.$folder->getKey())
      ->where('d.dashboard_id = '.$dashboard->getKey());
    $rowset = $this->database->fetchAll($sql);
    $results = array();
    foreach($rowset as $keyRow => $row)
      {
      $tmpDao = $this->initDao('ScalarResult', $row, 'validation');
      $this->database->removeLink('scores', $dashboard, $tmpDao);
      }
    $this->database->removeLink('results', $dashboard, $folder);
    }

  /**
   * Set a single row of result values for a dashboard.
   * @param dashboard the target dashboard
   * @param folder the result folder with which the values are associated
   * @param values an array where the keys are item ids and the values are
   *        scalar results
   * @return void
   */
  function setScores($dashboard, $folder, $values)
    {
    if(!$dashboard instanceof Validation_DashboardDao)
      {
      throw new Zend_Exception("Should be a dashboard.");
      }
    if(!$folder instanceof FolderDao)
      {
      throw new Zend_Exception("Should be a folder.");
      }
    $modelLoad = new MIDAS_ModelLoader();
    $scalarResultModel = $modelLoad->loadModel('ScalarResult', 'validation');
    $this->loadDaoClass('ScalarResultDao', 'validation');
    $items = $folder->getItems();
    $numItems = count($items);
    for($i = 0; $i < $numItems; ++$i)
      {
      $curItemKey = $items[$i]->getKey();
      $scalarResult = new Validation_ScalarResultDao();
      $scalarResult->setFolderId($folder->getKey());
      $scalarResult->setItemId($curItemKey);
      $scalarResult->setValue($values[$curItemKey]);
      $scalarResultModel->save($scalarResult);
      $this->database->link('scores', $dashboard, $scalarResult);
      }
    }

  /**
   * Set a single result value
   * @param dashboard the target dashboard
   * @param folder the result folder with which the value is associated
   * @param item the item associated with the result
   * @param value a scalar value representing a result
   *        scalar results
   * @return void
   */
  function setScore($dashboard, $folder, $item, $value)
    {
    if(!$dashboard instanceof Validation_DashboardDao)
      {
      throw new Zend_Exception("Should be a dashboard.");
      }
    if(!$folder instanceof FolderDao)
      {
      throw new Zend_Exception("Should be a folder.");
      }
    if(!$item instanceof ItemDao)
      {
      throw new Zend_Exception("Should be an item.");
      }
    $modelLoad = new MIDAS_ModelLoader();
    $scalarResultModel = $modelLoad->loadModel('ScalarResult', 'validation');
    $this->loadDaoClass('ScalarResultDao', 'validation');
    $items = $folder->getItems();
    $tgtItem = null;
    foreach($items as $curItem)
      {
      if($curItem->getKey() == $item->getKey())
        {
        $tgtItem = $curItem;
        break;
        }
      }
    if(!$tgtItem)
      {
      throw new ZendException('Target item not part of result set.');
      }

    // remove a previous scalar value if there is one.
    $oldResults = $scalarResultModel->findBy('item_id', $tgtItem->getKey());
    if(count($oldResults) == 1)
      {
      $oldResult = $oldResults[0];
      $this->database->removeLink('scores', $dashboard, $oldResult);
      }

    $scalarResult = new Validation_ScalarResultDao();
    $scalarResult->setFolderId($folder->getKey());
    $scalarResult->setItemId($tgtItem->getKey());
    $scalarResult->setValue($value);
    $scalarResultModel->save($scalarResult);
    $this->database->link('scores', $dashboard, $scalarResult);
    }

  /**
   * Get a single set of scores for a dashboard
   * @param dashboard the target dashboard
   * @param folder the folder that corresponds to the results
   * @return an array where the keys are item ids and the values are
   *         scores
   */
  function getScores($dashboard, $folder)
    {
    if(!$dashboard instanceof Validation_DashboardDao)
      {
      throw new Zend_Exception("Should be a dashboard.");
      }
    if(!$folder instanceof FolderDao)
      {
      throw new Zend_Exception("Should be a folder.");
      }
    $sql = $this->database->select()
      ->setIntegrityCheck(false)
      ->from(array('d' => 'validation_dashboard'))
      ->join(array('j' => 'validation_dashboard2scalarresult'),
             'd.dashboard_id = j.dashboard_id')
      ->join(array('r' => 'validation_scalarresult'),
             'j.scalarresult_id = r.scalarresult_id')
      ->where('r.folder_id = '.$folder->getKey())
      ->where('d.dashboard_id = '.$dashboard->getKey());
    $rowset = $this->database->fetchAll($sql);
    $results = array();
    foreach($rowset as $keyRow => $row)
      {
      $results[$row["item_id"]] = $row["value"];
      }
    return $results;
    }

  /**
   * Get all sets of scores for a dashboard
   * @param dashboard the target dashboard
   * @return an array of arrays where the keys are folder ids and the values
   *         are arrays where the keys are item ids and the values are
   *         scores
   */
  function getAllScores($dashboard)
    {
    if(!$dashboard instanceof Validation_DashboardDao)
      {
      throw new Zend_Exception("Should be a dashboard.");
      }

    $sql = $this->database->select()
      ->setIntegrityCheck(false)
      ->from(array('d' => 'validation_dashboard'))
      ->join(array('j' => 'validation_dashboard2scalarresult'),
             'd.dashboard_id = j.dashboard_id')
      ->join(array('r' => 'validation_scalarresult'),
             'j.scalarresult_id = r.scalarresult_id')
      ->where('d.dashboard_id = '.$dashboard->getKey());
    $rowset = $this->database->fetchAll($sql);
    $results = array();
    foreach($rowset as $keyRow => $row)
      {
      if(isset($results[$row["folder_id"]]))
        {
        $results[$row["folder_id"]][$row["item_id"]] = $row["value"];
        }
      else
        {
        $results[$row["folder_id"]] = array();
        $results[$row["folder_id"]][$row["item_id"]] = $row["value"];
        }
      }
    return $results;
    }

}  // end class
