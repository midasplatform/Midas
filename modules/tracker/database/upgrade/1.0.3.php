<?php

/**
 * Adds build results link
 */
class Tracker_Upgrade_1_0_3 extends MIDASUpgrade
{
  public function preUpgrade()
    {
    }

  public function mysql()
    {
    $this->db->query("ALTER TABLE `tracker_scalar` ADD COLUMN `build_results_url` text NOT NULL DEFAULT ''");
    }

  public function pgsql()
    {
    $this->db->query("ALTER TABLE tracker_scalar ADD COLUMN build_results_url text NOT NULL DEFAULT ''");
    }

  public function postUpgrade()
    {
    }
}
