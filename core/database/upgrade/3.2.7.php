<?php

/**
 * Upgrade 3.2.7 adds the progress table
 */
class Upgrade_3_2_7 extends MIDASUpgrade
{

  public function preUpgrade()
    {
    }

  public function mysql()
    {
    $this->db->query("CREATE TABLE `progress` (
      `progress_id` bigint(20) NOT NULL AUTO_INCREMENT,
      `message` TEXT NOT NULL DEFAULT '',
      `current` bigint(20) NOT NULL,
      `maximum` bigint(20) NOT NULL,
      `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `last_update` timestamp NOT NULL,
      PRIMARY KEY (`progress_id`)
      )");
    }

  public function pgsql()
    {
    $this->db->query("CREATE TABLE progress (
      progress_id serial PRIMARY KEY,
      message TEXT NOT NULL DEFAULT '',
      current bigint NOT NULL,
      maximum bigint NOT NULL,
      date_creation timestamp without time zone NOT NULL DEFAULT now(),
      last_update timestamp without time zone NOT NULL
      )");
    }

  public function postUpgrade()
    {
    }

}
?>
