<?php

/**
 * Adding indexes for faster lookup of the folder & item hierarchy
 */
class Upgrade_3_2_5 extends MIDASUpgrade
{
  public function preUpgrade()
    {
    }

  public function mysql()
    {
    $this->db->query("ALTER TABLE `folder` ADD INDEX (`left_indice`)");
    $this->db->query("ALTER TABLE `folder` ADD INDEX (`right_indice`)");
    $this->db->query("ALTER TABLE `item2folder` ADD INDEX (`folder_id`)");
    }

  public function pgsql()
    {
    $this->db->query("CREATE INDEX folder_idx_parent_id ON folder (parent_id)");
    $this->db->query("CREATE INDEX folder_idx_left_indice ON folder (left_indice)");
    $this->db->query("CREATE INDEX folder_idx_right_indice ON folder (right_indice)");
    $this->db->query("CREATE INDEX item2folder_idx_folder_id ON folder (folder_id)");
    }

  public function postUpgrade()
    {
    }
}
?>
