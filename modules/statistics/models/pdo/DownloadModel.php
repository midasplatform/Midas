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
}  // end class
?>
