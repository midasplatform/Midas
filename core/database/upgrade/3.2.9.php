<?php

/**
 * Upgrade 3.2.9 adds pgsql indicies on policy tables
 */
class Upgrade_3_2_9 extends MIDASUpgrade
  {
  public function preUpgrade()
    {
    }

  public function mysql()
    {
    }

  public function pgsql()
    {
    $this->db->query("ALTER TABLE itempolicygroup ADD CONSTRAINT itempolicygroup_item_group UNIQUE (item_id, group_id)");
    $this->db->query("ALTER TABLE itempolicyuser ADD CONSTRAINT itempolicyuser_item_user UNIQUE (item_id, user_id)");
    $this->db->query("ALTER TABLE folderpolicygroup ADD CONSTRAINT folderpolicygroup_folder_group UNIQUE (folder_id, group_id)");
    $this->db->query("ALTER TABLE folderpolicyuser ADD CONSTRAINT folderpolicyuser_folder_user UNIQUE (folder_id, user_id)");
    }

  public function postUpgrade()
    {
    }
  }
