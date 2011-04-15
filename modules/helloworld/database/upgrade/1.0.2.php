<?php

class Helloworld_Upgrade_1_0_2 extends MIDASUpgrade
{ 
  public $_moduleModels=array('Hello');
  public $_moduleDaos=array('Hello');
  
  public function preUpgrade()
    {
    $helloDao=new Helloworld_HelloDao();
    $this->Helloworld_Hello->save($helloDao);
    }
    
  public function mysql()
    {
    $sql = "CREATE TABLE IF NOT EXISTS helloworld_helloupgrade2 (
                  id int(11) NOT NULL AUTO_INCREMENT,
                  PRIMARY KEY (id)
                )";
    $this->db->query($sql);
    }
    
  public function pgsql()
    {
    
    }
    
  public function postUpgrade()
    {
    
    }
}
?>
