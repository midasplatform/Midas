<?php

class Upgrade_3_0_7 extends MIDASUpgrade
{ 
  public function preUpgrade()
    {
    
    }
    
  public function mysql()
    {
    $sql = "ALTER TABLE community MODIFY moderatorgroup_id bigint(20) NULL DEFAULT NULL;  ";
    $this->db->query($sql);
    }

    
  public function pgsql()
    {
    $sql = "ALTER TABLE community ALTER COLUMN membergroup_id DROP NOT NULL; ; ";
    $this->db->query($sql);
    }
    
  public function postUpgrade()
    {
    
    }
}
?>


