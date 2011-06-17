<?php

class Upgrade_3_0_10 extends MIDASUpgrade
{ 
  public function preUpgrade()
    {
    }
    
  public function mysql()
    {
    $this->db->query("DROP TABLE metadatatype");
    $this->renameTableField('metadata','metadatatype_id','metadatatype','int(11)','integer','0');
    }

    
  public function pgsql()
    {
    $this->db->query("DROP TABLE metadatatype");
    $this->renameTableField('metadata','metadatatype_id','metadatatype','int(11)','integer','0');
    }
    
  public function postUpgrade()
    {
    
    }
}
?>


