<?php

/**
 * Adds user agent to the download record
 */
class Statistics_Upgrade_1_0_2 extends MIDASUpgrade
{
  public function preUpgrade()
    {
    }

  /** Mysql upgrade */
  public function mysql()
    {
    $this->db->query("ALTER TABLE `statistics_download`
                      ADD COLUMN `user_agent` VARCHAR(255) DEFAULT ''");
    }

  public function postUpgrade()
    {
    }
}
?>
