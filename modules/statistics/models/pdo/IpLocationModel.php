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

/** statistics Ip Location model */
class Statistics_IpLocationModel extends Statistics_IpLocationModelBase
{
  /**
   * Return the record for the given ip
   */
  function getByIp($ip)
    {
    $sql = $this->database->select()
            ->setIntegrityCheck(false)
            ->from(array('e' => 'statistics_ip_location'))
            ->where('ip = ?', $ip);
    $rowset = $this->database->fetchAll($sql);
    foreach($rowset as $keyRow => $row)
      {
      return $this->initDao('IpLocation', $row, 'statistics');
      }
    return false;
    }

  /**
   * Return entries that have not yet had geolocation run on them
   */
  function getAllUnlocated()
    {
    $result = array();
    $sql = $this->database->select()
            ->setIntegrityCheck(false)
            ->from(array('e' => 'statistics_ip_location'))
            ->where('latitude = ?', '');
    $rowset = $this->database->fetchAll($sql);
    foreach($rowset as $keyRow => $row)
      {
      $result[] = $this->initDao('IpLocation', $row, 'statistics');
      }
    return $result;
    }
}  // end class
?>
