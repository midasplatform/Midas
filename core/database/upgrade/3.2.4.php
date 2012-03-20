<?php

/**
 * Adds a table that will allow an ip to be locked during download
 * so that a single ip cannot flood the server with downloads.
 */
class Upgrade_3_2_4 extends MIDASUpgrade
{
  public function preUpgrade()
    {
    }

  public function mysql()
    {
    $this->db->query("CREATE TABLE `activedownload` (
      `activedownload_id` bigint(20) NOT NULL AUTO_INCREMENT,
      `ip` varchar(50) NOT NULL DEFAULT '',
      `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `last_update` timestamp NOT NULL,
      PRIMARY KEY (`activedownload_id`),
      KEY `ip` (`ip`)
      )");
    }

  public function pgsql()
    {
    $this->db->query("CREATE TABLE activedownload (
      activedownload_id serial PRIMARY KEY,
      ip character varying(50) NOT NULL DEFAULT '',
      date_creation timestamp without time zone NOT NULL DEFAULT now(),
      last_update timestamp without time zone NOT NULL
      )");
    $this->db->query("CREATE INDEX activedownload_idx_ip ON activedownload (ip)");
    }

  public function postUpgrade()
    {
    }
}
?>
