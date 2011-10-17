<?php

class Upgrade_3_1_2 extends MIDASUpgrade
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
    $this->addTableField('user', 'dynamichelp', 'tinyint(4)', ' integer', 1);
    }
}
?>


