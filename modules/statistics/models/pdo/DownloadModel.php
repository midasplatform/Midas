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

require_once BASE_PATH.'/modules/statistics/models/base/DownloadBase.php';

/** statistics_download model */
class Statistics_DownloadModel extends Statistics_DownloadModelBase
{
  /**
   * Return a list of downloads
   * @param type $startDate
   * @param type $endDate
   * @param type $module
   * @param type $priority
   * @param type $limit
   * @return array ErrorlogDao
   */
  function getDownloads($item, $startDate, $endDate, $limit = 99999)
    {
    $result = array();
    $sql = $this->database->select()
            ->setIntegrityCheck(false)
            ->from(array('e' => 'statistics_download'))
            ->where('date >= ?', $startDate)
            ->where('date <= ?', $endDate)
            ->where('item_id = ?', $item->getKey())
            ->order('date DESC')
            ->limit($limit);
    $rowset = $this->database->fetchAll($sql);
    foreach($rowset as $keyRow => $row)
      {
      $result[] = $this->initDao('Download', $row, 'statistics');
      }
    return $result;
    }

  /** Return only downloads that have been successfully geolocated */
  function getLocatedDownloads($item, $startDate, $endDate, $limit = 99999)
    {
    $result = array();
    $sql = $this->database->select()
            ->setIntegrityCheck(false)
            ->from(array('d' => 'statistics_download'))
            ->joinLeft(array('ipl' => 'statistics_ip_location'), 'd.ip_location_id = ipl.ip_location_id')
            ->where('date >= ?', $startDate)
            ->where('date <= ?', $endDate)
            ->where('item_id = ?', $item->getKey())
            ->where('latitude != 0')
            ->where('latitude != ?', '')
            ->order('date DESC')
            ->limit($limit);
    $rowset = $this->database->fetchAll($sql);
    foreach($rowset as $keyRow => $row)
      {
      $result[] = $this->initDao('Download', $row, 'statistics');
      }
    return $result;
    }

  /**
   * Return a list of downloads that have not yet had geolocation run on them
   */
  function getAllUnlocated()
    {
    $result = array();
    $sql = $this->database->select()
            ->setIntegrityCheck(false)
            ->from(array('e' => 'statistics_download'))
            ->where('latitude = ?', '');
    $rowset = $this->database->fetchAll($sql);
    foreach($rowset as $keyRow => $row)
      {
      $result[] = $this->initDao('Download', $row, 'statistics');
      }
    return $result;
    }

  /**
   * Set user id = NULL for all entries in the database referencing the user.
   * Called when a user is about to be deleted
   * @param userId The id of the user being deleted.
   */
  function removeUserReferences($userId)
    {
    $this->database->update(array('user_id' => null), array('user_id = ?' => $userId));
    }
}  // end class
?>
