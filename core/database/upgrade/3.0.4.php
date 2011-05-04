<?php

class Upgrade_3_0_4 extends MIDASUpgrade
{ 
  public function preUpgrade()
    {
    
    }
    
  public function mysql()
    {
    $sql = "ALTER TABLE folder ADD COLUMN teaser  varchar(250) DEFAULT ''; ";
    $this->db->query($sql);
    }
    
  public function pgsql()
    {
    $sql = "ALTER TABLE folder ADD COLUMN teaser  varying(250)  DEFAULT '', ; ";
    $this->db->query($sql);
    }
    
  public function postUpgrade()
    {
    
    }
}
?>
