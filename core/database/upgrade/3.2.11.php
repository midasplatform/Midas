<?php

/**
 * Upgrade 3.2.11 removes the public folder and private folder columns
 * from community and user tables
 */
class Upgrade_3_2_11 extends MIDASUpgrade
{

  public function preUpgrade()
    {
    }

  public function mysql()
    {
    $this->db->query("ALTER TABLE `community` DROP `publicfolder_id`");
    $this->db->query("ALTER TABLE `community` DROP `privatefolder_id`");
    $this->db->query("ALTER TABLE `user` DROP `publicfolder_id`");
    $this->db->query("ALTER TABLE `user` DROP `privatefolder_id`");
    }

  public function pgsql()
    {
    $this->db->query("ALTER TABLE community DROP COLUMN publicfolder_id");
    $this->db->query("ALTER TABLE community DROP COLUMN privatefolder_id");
    $this->db->query("ALTER TABLE \"user\" DROP COLUMN publicfolder_id");
    $this->db->query("ALTER TABLE \"user\" DROP COLUMN privatefolder_id");
    }

  public function postUpgrade()
    {
    }

}
