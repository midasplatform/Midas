<?php

/**
 * Add a table that will store a separate thumbnail for an item
 */
class Thumbnailcreator_Upgrade_1_0_1 extends MIDASUpgrade
{
  public function preUpgrade()
    {
    }

  /** Mysql upgrade */
  public function mysql()
    {
    $this->db->query("CREATE TABLE IF NOT EXISTS thumbnailcreator_itemthumbnail (
      `itemthumbnail_id` bigint(20) NOT NULL AUTO_INCREMENT,
      `item_id` bigint(20),
      `thumbnail` varchar(255),
      INDEX(`item_id`),
      PRIMARY KEY (`itemthumbnail_id`)
      )");
    }

  /** Pgsql upgrade */
  public function pgsql()
    {
    $this->db->query("CREATE TABLE thumbnailcreator_itemthumbnail (
      itemthumbnail_id serial PRIMARY KEY,
      item_id integer,
      thumbnail character varying(255)
      )");
    $this->db->query("CREATE INDEX thumbnailcreator_itemthumbnail_item_id ON thumbnailcreator_itemthumbnail (item_id)");
    }

  public function postUpgrade()
    {
    }
}
?>
