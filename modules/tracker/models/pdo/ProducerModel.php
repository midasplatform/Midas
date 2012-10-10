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
require_once BASE_PATH.'/modules/tracker/models/base/ProducerModelBase.php';

/**
 * Producer PDO Model
 */
class Tracker_ProducerModel extends Tracker_ProducerModelBase
{
  /**
   * Return all producers for the given community
   */
  public function getByCommunityId($communityId)
    {
    $sql = $this->database->select()
                          ->setIntegrityCheck(false)
                          ->where('community_id = ?', $communityId);
    $rowset = $this->database->fetchAll($sql);
    $producers = array();
    foreach($rowset as $row)
      {
      $producers[] = $this->initDao('Producer', $row, $this->moduleName);
      }
    return $producers;
    }

  /**
   * Return the producer with the given display name under the given community
   */
  public function getByCommunityIdAndName($communityId, $displayName)
    {
    $sql = $this->database->select()
                          ->setIntegrityCheck(false)
                          ->where('community_id = ?', $communityId)
                          ->where('display_name = ?', $displayName);
    return $this->initDao('Producer', $this->database->fetchRow($sql), $this->moduleName);
    }
}
