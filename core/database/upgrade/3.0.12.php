<?php

class Upgrade_3_0_12 extends MIDASUpgrade
{ 
  public function preUpgrade()
    {
    
    }
    
  public function mysql()
    {
    $sql = "DROP  TABLE uniqueidentifier ; ";
    $this->db->query($sql);
    }

    
  public function pgsql()
    {
    $sql = "DROP  TABLE uniqueidentifier; ";
    $this->db->query($sql);
    }
    
  public function postUpgrade()
    {
    $this->AddTableField('community', 'uuid', 'varchar(255)', ' character varying(512)', null);
    $this->AddTableField('user', 'uuid', 'varchar(255)', ' character varying(512)', null);
    $this->AddTableField('item', 'uuid', 'varchar(255)', ' character varying(512)', null);
    $this->AddTableField('folder', 'uuid', 'varchar(255)', ' character varying(512)', null);
    $this->AddTableField('itemrevision', 'uuid', 'varchar(255)', ' character varying(512)', null);

    }
}
?>


