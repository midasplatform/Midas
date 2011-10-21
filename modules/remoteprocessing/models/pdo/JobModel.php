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
}  // end class
?>