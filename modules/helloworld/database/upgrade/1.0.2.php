<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/

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
                ) DEFAULT CHARSET=utf8";
    $this->db->query($sql);
    }
    
  public function pgsql()
    {
    $sql = "CREATE TABLE helloworld_helloupgrade2 (
                  id serial PRIMARY KEY
                  );";
    $this->db->query($sql);
    }
    
  public function postUpgrade()
    {
    
    }
}
?>
