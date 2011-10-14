<?php

class Upgrade_3_0_9 extends MIDASUpgrade
{ 
  public function preUpgrade()
    {
    
    }
    
  public function mysql()
    {
    $sql = "
      CREATE TABLE IF NOT EXISTS `uniqueidentifier` (
        `uniqueidentifier_id` varchar(255) NOT NULL,
        `resource_id` bigint(20),
        `resource_type` tinyint(4),
        PRIMARY KEY (`uniqueidentifier_id`)
      )   DEFAULT CHARSET=utf8 AUTO_INCREMENT=10 ;
      ";
    $this->db->query($sql);
    }

    
  public function pgsql()
    {
    $sql = "
      CREATE TABLE  uniqueidentifier (
        uniqueidentifier_id character varying(512)  PRIMARY KEY,
        resource_type  integer,
        resource_id bigint
      )  
      ; ";
    $this->db->query($sql);
    }
    
  public function postUpgrade()
    {
    
    }
}
?>


