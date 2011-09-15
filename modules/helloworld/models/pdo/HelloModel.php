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
require_once BASE_PATH.'/modules/helloworld/models/base/HelloModelBase.php';

/** demo pdo model */

class Helloworld_HelloModel extends Helloworld_HelloModelBase
{
  /**
   * Return all the record in the table
   * @return Array of HelloDao
   */
  function getAll()
    {
    $sql = $this->database->select();
    $rowset = $this->database->fetchAll($sql);
    $rowsetAnalysed = array();
    foreach($rowset as $keyRow => $row)
      {
      $tmpDao = $this->initDao('Hello', $row, 'helloworld');
      $rowsetAnalysed[] = $tmpDao;
      }
    return $rowsetAnalysed;
    }

}  // end class
