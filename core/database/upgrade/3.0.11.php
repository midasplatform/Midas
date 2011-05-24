<?php

class Upgrade_3_0_11 extends MIDASUpgrade
{ 
  public function preUpgrade()
    {
    
    }
    
  public function mysql()
    {
    $sql = "
      CREATE TABLE IF NOT EXISTS `communityinvitation` (
        `communityinvitation_id` bigint(20) NOT NULL AUTO_INCREMENT,
        `community_id` bigint(20),
        `user_id` bigint(20),
        PRIMARY KEY (`communityinvitation_id`)
      ) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=10 ;
      ";
    $this->db->query($sql);
    }

    
  public function pgsql()
    {
    $sql = "
      CREATE TABLE  communityinvitation (
        communityinvitation_id  serial  PRIMARY KEY,
        community_id bigint,
        user_id bigint
      )  
      ; ";
    $this->db->query($sql);
    }
    
  public function postUpgrade()
    {
    
    }
}
?>


