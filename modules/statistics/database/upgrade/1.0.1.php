<?php

class Statistics_Upgrade_1_0_1 extends MIDASUpgrade
{
  public function preUpgrade()
    {
    }

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
              ADD COLUMN `ip_id` bigint(20) NOT NULL");

    // Copy the entries from our old table into the new one
    $sql = $this->db->select()
            ->from(array('d' => 'statistics_download'), array('ip', 'latitude', 'longitude'))
            ->distinct();
    $rowSet = $this->db->fetchAll($sql);
    foreach($rowSet as $keyRow => $row)
      {
      $data = array();
      $data['ip'] = $row['ip'];
      $data['latitude'] = $row['latitude'];
      $data['longitude'] = $row['longitude'];
      $id = $this->db->insert('statistics_ip_location', $data);

      // Point the download table entries to the new entry
      $this->db->update('statistics_download',
                        array('ip_id' => $id),
                        array('ip = ?' => $row['ip']));
      }

    // Drop the columns from the download table
    $this->db->query("ALTER TABLE `statistics_download` DROP `ip`");
    $this->db->query("ALTER TABLE `statistics_download` DROP `latitude`");
    $this->db->query("ALTER TABLE `statistics_download` DROP `longitude`");

    // Add item id index to the download table for faster item statistics lookup
    $this->db->query("ALTER TABLE `statistics_download` ADD INDEX (`item_id`)");
    }

  public function postUpgrade()
    {
    }
}
?>
