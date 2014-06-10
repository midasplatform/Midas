<?php

/**
 * Adds branch name to scalar submission table, and adds an index for it.
 */
class Tracker_Upgrade_1_0_4 extends MIDASUpgrade
{
  public function preUpgrade()
    {
    }

  public function mysql()
    {
    $this->db->query("ALTER TABLE `tracker_scalar` ADD COLUMN `branch` varchar(255) NOT NULL DEFAULT ''");

    $this->db->query("ALTER TABLE `tracker_scalar` ADD KEY (`branch`)");
    }

  public function pgsql()
    {
    $this->db->query("ALTER TABLE tracker_scalar ADD COLUMN branch character varying(255) NOT NULL DEFAULT ''");

    $this->db->query("CREATE INDEX tracker_scalar_idx_branch ON tracker_scalar (branch)");
    }

  public function postUpgrade()
    {
    }
}
