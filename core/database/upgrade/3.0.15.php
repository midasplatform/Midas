<?php

class Upgrade_3_0_15 extends MIDASUpgrade
{ 
  public function preUpgrade()
    {             
    }
    
  public function mysql()
    {
    }

    
  public function pgsql()
    {
    }
    
  public function postUpgrade()
    {
    $this->addTableField('user', 'city', 'varchar(100)', ' character varying(100)', null);
    $this->addTableField('user', 'country', 'varchar(100)', ' character varying(100)', null);
    $this->addTableField('user', 'website', 'varchar(255)', ' character varying(255)', null);
    $this->addTableField('user', 'biography', 'varchar(255)', ' character varying(255)', null);
    }
}
?>


