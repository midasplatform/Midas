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

}  // end class
