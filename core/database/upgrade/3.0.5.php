<?php

class Upgrade_3_0_5 extends MIDASUpgrade
{ 
  public function preUpgrade()
    {
    
    }
    
  public function mysql()
    {
    $sql = "ALTER TABLE community MODIFY folder_id bigint(20) NULL DEFAULT NULL;  ";
    $this->db->query($sql);
    $sql = "ALTER TABLE community MODIFY publicfolder_id bigint(20) NULL DEFAULT NULL;  ";
    $this->db->query($sql);
    $sql = "ALTER TABLE community MODIFY privatefolder_id bigint(20) NULL DEFAULT NULL;  ";
    $this->db->query($sql);
    }
    
  public function pgsql()
    {
    $sql = "ALTER TABLE community ALTER COLUMN folder_id DROP NOT NULL; ; ";
    $this->db->query($sql);
    $sql = "ALTER TABLE community ALTER COLUMN publicfolder_id DROP NOT NULL; ; ";
    $this->db->query($sql);
    $sql = "ALTER TABLE community ALTER COLUMN privatefolder_id DROP NOT NULL; ; ";
    $this->db->query($sql);
    }
    
  public function postUpgrade()
    {
    
    }
}
?>
