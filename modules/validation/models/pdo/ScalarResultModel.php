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
require_once BASE_PATH.'/modules/validation/models/base/ScalarResultModelBase.php';

/**
 * ScalarResult PDO Model
 */
class Validation_ScalarResultModel extends Validation_ScalarResultModelBase
{
  /**
   * Return all the record in the table
   * @return array of ScalarResultDao
   */
  function getAll()
    {
    $sql = $this->database->select();
    $rowset = $this->database->fetchAll($sql);
    $rowsetAnalysed = array();
    foreach($rowset as $keyRow => $row)
      {
      $tmpDao = $this->initDao('ScalarResult', $row, 'validation');
      $rowsetAnalysed[] = $tmpDao;
      }
    return $rowsetAnalysed;
    }
}
