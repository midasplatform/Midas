<?php

class Upgrade_3_0_3 extends MIDASUpgrade
{ 
  public function preUpgrade()
    {
    
    }
    
  public function mysql()
    {
    $sql = "ALTER TABLE community ADD COLUMN can_join integer DEFAULT 0; ";
    $this->db->query($sql);
    }
    
  public function pgsql()
    {
    $sql = "ALTER TABLE community ADD COLUMN can_join integer NOT NULL  DEFAULT '0' ; ";
    $this->db->query($sql);
    }
    
  public function postUpgrade()
    {
    
    }
}
?>
