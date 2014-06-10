<?php

/**
 * Upgrade 3.2.14 fixes bug #1001: folder modified time changed incorrectly
 */
class Upgrade_3_2_14 extends MIDASUpgrade
{

  public function preUpgrade()
    {
    }

  public function mysql()
    {
    // Remove "on update current timestamp" qualifier from the date_update column
    $this->db->query("ALTER TABLE `folder`
      CHANGE `date_update` `date_update` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
    $this->db->query("ALTER TABLE `item`
      CHANGE `date_update` `date_update` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
    $this->db->query("ALTER TABLE `itemrevision`
      CHANGE `date` `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
    $this->db->query("ALTER TABLE `bitstream`
      CHANGE `date` `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
    $this->db->query("ALTER TABLE `feed`
      CHANGE `date` `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
    }

  public function pgsql()
    {
    // bug only existed in our mysql table defs, NOP for pgsql
    }

  public function postUpgrade()
    {
    }
}
