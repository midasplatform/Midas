<?php

/**
 * Adds revision URL as a configuration parameter on producers
 */
class Tracker_Upgrade_1_0_2 extends MIDASUpgrade
{
  public function preUpgrade()
    {
    }

  public function mysql()
    {
    $this->db->query("ALTER TABLE `tracker_producer` ADD COLUMN `revision_url` text NOT NULL");
    }

  public function pgsql()
    {
    $this->db->query("ALTER TABLE tracker_producer ADD COLUMN revision_url text NOT NULL DEFAULT ''");
    }

  public function postUpgrade()
    {
    }
}
