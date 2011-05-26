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
    $this->addTableField('community', 'uuid', 'varchar(255)', ' character varying(512)', null);
    $this->addTableField('user', 'uuid', 'varchar(255)', ' character varying(512)', null);
    $this->addTableField('item', 'uuid', 'varchar(255)', ' character varying(512)', null);
    $this->addTableField('folder', 'uuid', 'varchar(255)', ' character varying(512)', null);
    $this->addTableField('itemrevision', 'uuid', 'varchar(255)', ' character varying(512)', null);

    }
}
?>


