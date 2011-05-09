<?php

class Upgrade_3_0_6 extends MIDASUpgrade
{ 
  public function preUpgrade()
    {
    
    }
    
  public function mysql()
    {
    $sql = "ALTER TABLE community MODIFY admingroup_id bigint(20) NULL DEFAULT NULL;  ";
    $this->db->query($sql);
    $sql = "ALTER TABLE community MODIFY moderatorgroup_id bigint(20) NULL DEFAULT NULL;  ";
    $this->db->query($sql);
    }

    
  public function pgsql()
    {
    $sql = "ALTER TABLE community ALTER COLUMN admingroup_id DROP NOT NULL; ; ";
    $this->db->query($sql);
    $sql = "ALTER TABLE community ALTER COLUMN membergroup_id DROP NOT NULL; ; ";
    $this->db->query($sql);
    }
    
  public function postUpgrade()
    {
    
    }
}
?>

