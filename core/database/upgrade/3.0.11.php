<?php
/*=========================================================================
 MIDAS Server
 Copyright (c) Kitware SAS. 26 rue Louis Guérin. 69100 Villeurbanne, FRANCE
 All rights reserved.
 More information http://www.kitware.com

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

         http://www.apache.org/licenses/LICENSE-2.0.txt

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
=========================================================================*/

class Upgrade_3_0_11 extends MIDASUpgrade
{ 
  public function preUpgrade()
    {
    
    }
    
  public function mysql()
    {
    $sql = "
      CREATE TABLE IF NOT EXISTS `communityinvitation` (
        `communityinvitation_id` bigint(20) NOT NULL AUTO_INCREMENT,
        `community_id` bigint(20),
        `user_id` bigint(20),
        PRIMARY KEY (`communityinvitation_id`)
      )   DEFAULT CHARSET=utf8;
      ";
    $this->db->query($sql);
    }

    
  public function pgsql()
    {
    $sql = "
      CREATE TABLE  communityinvitation (
        communityinvitation_id  serial  PRIMARY KEY,
        community_id bigint,
        user_id bigint
      )  
      ; ";
    $this->db->query($sql);
    }
    
  public function postUpgrade()
    {
    
    }
}
?>


