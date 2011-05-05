<?php

class Upgrade_3_0_2 extends MIDASUpgrade
{ 
  public function preUpgrade()
    {
    
    }
    
  public function mysql()
    {
    $sql = "ALTER TABLE itemrevision ADD COLUMN license integer DEFAULT 0; ";
    $this->db->query($sql);
    }
    
  public function pgsql()
    {
    $sql = "ALTER TABLE itemrevision ADD COLUMN license integer NOT NULL  DEFAULT '0' ; ";
    $this->db->query($sql);
    }
    
  public function postUpgrade()
    {
    
    }
}
?>
