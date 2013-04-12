<?php

/**
 * 1. Adds a user_id value to a scalar record indicating which user uploaded the scalar
 * 2. Adds a binary "official" flag to a scalar record indicating if it is an official or experimental submission
 * 3. Adds submit_time and user_id indices to the tracker_scalar table
 */
class Tracker_Upgrade_1_0_1 extends MIDASUpgrade
{
  public function preUpgrade()
    {
    }

  public function mysql()
    {
    $this->db->query("ALTER TABLE `tracker_scalar` ADD COLUMN `user_id` bigint(20) NOT NULL DEFAULT -1");
    $this->db->query("ALTER TABLE `tracker_scalar` ADD COLUMN `official` tinyint(4) NOT NULL DEFAULT 1");

    $this->db->query("ALTER TABLE `tracker_scalar` ADD INDEX (`submit_time`)");
    $this->db->query("ALTER TABLE `tracker_scalar` ADD INDEX (`user_id`)");
    }

  public function pgsql()
    {
    $this->db->query("ALTER TABLE tracker_scalar ADD COLUMN user_id bigint NOT NULL DEFAULT -1");
    $this->db->query("ALTER TABLE tracker_scalar ADD COLUMN official smallint NOT NULL DEFAULT 1");

    $this->db->query("CREATE INDEX tracker_scalar_idx_submit_time ON tracker_scalar (submit_time)");
    $this->db->query("CREATE INDEX tracker_scalar_idx_user_id ON tracker_scalar (user_id)");
    }

  public function postUpgrade()
    {
    }
}
?>
