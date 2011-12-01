<?php

class Statistics_Upgrade_1_0_1 extends MIDASUpgrade
{
  public function preUpgrade()
    {
    }

  /**
   * Adds an index on latitude field so that the geolocation
   * job can quickly find the entries that need to be geolocated.
   * Adds an index on item_id field to make item-level statistics lookup faster.
   */
  public function mysql()
    {
    $sql = "ALTER TABLE `statistics_download`
            ADD INDEX (`latitude`)";
    $this->db->query($sql);

    $sql = "ALTER TABLE `statistics_download`
            ADD INDEX (`item_id`)";
    $this->db->query($sql);
    }


  public function postUpgrade()
    {
    }
}
?>
