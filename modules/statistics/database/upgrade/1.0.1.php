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

class Statistics_Upgrade_1_0_1 extends MIDASUpgrade
  {
  /**
   * Normalize the download table -- pull out ip to lat/long mapping
   * into its own table, and add an index on download.item_id field to make
   * item-level statistics lookup faster.
   */
  public function mysql()
    {
    // Create a new table mapping ip -> lat/long
    $this->db->query("CREATE TABLE `statistics_ip_location` (
              `ip_location_id` bigint(20) NOT NULL AUTO_INCREMENT,
              `ip` VARCHAR(50) NOT NULL,
              `latitude` VARCHAR(50) NOT NULL,
              `longitude` VARCHAR(50) NOT NULL,
              PRIMARY KEY (`ip_location_id`),
              UNIQUE KEY (`ip`) )");

    // Add a logical foreign key into the download table
    $this->db->query("ALTER TABLE `statistics_download`
              ADD COLUMN `ip_location_id` bigint(20) NOT NULL");

    // Copy the entries from our old table into the new one
    $sql = $this->db->select()
            ->from(array('d' => 'statistics_download'), array('ip', 'latitude', 'longitude'))
            ->distinct();
    $rowSet = $this->db->fetchAll($sql);
    $ips = array();
    foreach($rowSet as $row)
      {
      // key by ip so we don't have duplicate ips (violates unique constraint)
      $ips[$row['ip']] = array('latitude' => $row['latitude'], 'longitude' => $row['longitude']);
      }
    foreach($ips as $ip => $location)
      {
      $data = array('ip' => $ip, 'latitude' => $location['latitude'], 'longitude' => $location['longitude']);
      $this->db->insert('statistics_ip_location', $data);
      $id = $this->db->lastInsertId('statistics_ip_location', 'ip_location_id');

      // Point the download table entries to the new entry
      $this->db->update('statistics_download',
                        array('ip_location_id' => $id),
                        array('ip = ?' => $ip));
      }

    // Drop the columns from the download table
    $this->db->query("ALTER TABLE `statistics_download` DROP `ip`");
    $this->db->query("ALTER TABLE `statistics_download` DROP `latitude`");
    $this->db->query("ALTER TABLE `statistics_download` DROP `longitude`");

    // Add item id index to the download table for faster item statistics lookup
    $this->db->query("ALTER TABLE `statistics_download` ADD INDEX (`item_id`)");
    // Add latitude index to the geolocation table for quick selection of blank entries
    $this->db->query("ALTER TABLE `statistics_ip_location` ADD INDEX (`latitude`)");
    }
  }
