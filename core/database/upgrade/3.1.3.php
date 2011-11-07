<?php

class Upgrade_3_1_3 extends MIDASUpgrade
{ 
  public function preUpgrade()
    {             
    }
    
  public function mysql()
    {
    $sql = "ALTER TABLE item MODIFY description TEXT NOT NULL;";
    $this->db->query($sql);
    }

    
  public function pgsql()
    {
    $sql = "ALTER TABLE item ALTER COLUMN description TYPE TEXT;";
    $this->db->query($sql);
    }
    
  public function postUpgrade()
    {
    }
}
?>