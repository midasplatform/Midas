<?php

class Upgrade_3_0_8 extends MIDASUpgrade
{ 
  public function preUpgrade()
    {
    
    }
    
  public function mysql()
    {
    $sql = "ALTER TABLE folder ADD COLUMN privacy_status  integer DEFAULT 0; ";
    $this->db->query($sql);
    $sql = "ALTER TABLE item ADD COLUMN privacy_status  integer DEFAULT 0; ";
    $this->db->query($sql);
    }
    
  public function pgsql()
    {
    $sql = "ALTER TABLE folder ADD COLUMN privacy_status  integer NOT NULL  DEFAULT '0' ; ";
    $this->db->query($sql);
    $sql = "ALTER TABLE item ADD COLUMN privacy_status  integer NOT NULL  DEFAULT '0' ; ";
    $this->db->query($sql);
    }
    
  public function postUpgrade()
    {
    
    }
}
?>


