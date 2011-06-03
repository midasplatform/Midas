<?php

class Upgrade_3_0_16 extends MIDASUpgrade
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
    $this->renameTableField('item', 'date', 'date_update', 'timestamp', 'timestamp without time zone', false);
    $this->addTableField('item', 'date_creation', 'timestamp', 'timestamp without time zone', false);
    $this->renameTableField('folder', 'date', 'date_update', 'timestamp', 'timestamp without time zone', false);
    $this->addTableField('folder', 'date_creation', 'timestamp', 'timestamp without time zone', false);
    }
}
?>


