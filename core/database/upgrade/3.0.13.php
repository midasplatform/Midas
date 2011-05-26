<?php

class Upgrade_3_0_13 extends MIDASUpgrade
{ 
  public function preUpgrade()
    {
    $this->AddTableField('metadatadocumentvalue', 'metadatavalue_id', 'bigint(20)', 'serial',false);
    $this->AddTableField('metadatavalue', 'metadatavalue_id', 'bigint(20)', 'serial',false);
    }
    
  public function mysql()
    {
    $this->AddTablePrimaryKey('metadatadocumentvalue', 'metadatavalue_id');
    $this->AddTablePrimaryKey('metadatavalue', 'metadatavalue_id'); 
    $this->db->query("ALTER TABLE `metadatadocumentvalue` CHANGE `metadatavalue_id` 
                      `metadatavalue_id` BIGINT( 20 ) NOT NULL AUTO_INCREMENT");
    $this->db->query("ALTER TABLE `metadatavalue` CHANGE `metadatavalue_id` 
                      `metadatavalue_id` BIGINT( 20 ) NOT NULL AUTO_INCREMENT");
    }

    
  public function pgsql()
    {
    }
    
  public function postUpgrade()
    {
    }
}
?>


