<?php

class Upgrade_3_1_0 extends MIDASUpgrade
{ 
  public function preUpgrade()
    {             
    }
    
  public function mysql()
    {
    }

    
  public function pgsql()
    {
    $this->db->query('ALTER TABLE community ALTER COLUMN moderatorgroup_id	 DROP NOT NULL');    
    }
    
  public function postUpgrade()
    {
    }
}
?>


