<?php

class Upgrade_3_0_1 extends MIDASUpgrade
{ 
  public function preUpgrade()
    {
    
    }
    
  public function mysql()
    {
    $sql = "ALTER TABLE folderpolicygroup ADD COLUMN date timestamp DEFAULT CURRENT_TIMESTAMP(); ";
    $this->db->query($sql);
    $sql = "ALTER TABLE folderpolicyuser ADD COLUMN date timestamp DEFAULT CURRENT_TIMESTAMP(); ";
    $this->db->query($sql);
    $sql = "ALTER TABLE itempolicygroup ADD COLUMN date timestamp DEFAULT CURRENT_TIMESTAMP(); ";
    $this->db->query($sql);
    $sql = "ALTER TABLE itempolicyuser ADD COLUMN date timestamp DEFAULT CURRENT_TIMESTAMP(); ";
    $this->db->query($sql);
    $sql = "ALTER TABLE feedpolicygroup ADD COLUMN date timestamp DEFAULT CURRENT_TIMESTAMP(); ";
    $this->db->query($sql);
    $sql = "ALTER TABLE feedpolicyuser ADD COLUMN date timestamp DEFAULT CURRENT_TIMESTAMP(); ";
    $this->db->query($sql);
    }
    
  public function pgsql()
    {
    $sql = "ALTER TABLE folderpolicygroup ADD COLUMN date TIMESTAMP DEFAULT CURRENT_TIMESTAMP; ";
    $this->db->query($sql);
    $sql = "ALTER TABLE folderpolicyuser ADD COLUMN date TIMESTAMP DEFAULT CURRENT_TIMESTAMP; ";
    $this->db->query($sql);
    $sql = "ALTER TABLE itempolicygroup ADD COLUMN date TIMESTAMP DEFAULT CURRENT_TIMESTAMP; ";
    $this->db->query($sql);
    $sql = "ALTER TABLE itempolicyuser ADD COLUMN date TIMESTAMP DEFAULT CURRENT_TIMESTAMP; ";
    $this->db->query($sql);
    $sql = "ALTER TABLE feedpolicygroup ADD COLUMN date TIMESTAMP DEFAULT CURRENT_TIMESTAMP; ";
    $this->db->query($sql);
    $sql = "ALTER TABLE feedpolicyuser ADD COLUMN date TIMESTAMP DEFAULT CURRENT_TIMESTAMP; ";
    $this->db->query($sql);
    }
    
  public function postUpgrade()
    {
    
    }
}
?>
