<?php

class Upgrade_3_1_1 extends MIDASUpgrade
{ 
  public function preUpgrade()
    {             
    }
    
  public function mysql()
    {
    $this->db->query('DROP TABLE itemkeyword');  
    $this->db->query('DROP TABLE item2keyword');  
    }

    
  public function pgsql()
    {
    $this->db->query('DROP TABLE itemkeyword');  
    $this->db->query('DROP TABLE item2keyword');  
    }
    
  public function postUpgrade()
    {
    }
}
?>


