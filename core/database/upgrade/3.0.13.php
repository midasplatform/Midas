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

class Upgrade_3_0_13 extends MIDASUpgrade
{ 
  public function preUpgrade()
    {
    $this->addTableField('metadatadocumentvalue', 'metadatavalue_id', 'bigint(20)', 'serial',false);
    $this->addTableField('metadatavalue', 'metadatavalue_id', 'bigint(20)', 'serial',false);
    }
    
  public function mysql()
    {
    $this->addTablePrimaryKey('metadatadocumentvalue', 'metadatavalue_id');
    $this->addTablePrimaryKey('metadatavalue', 'metadatavalue_id'); 
    $this->db->query("ALTER TABLE `metadatadocumentvalue` CHANGE `metadatavalue_id` 
                      `metadatavalue_id` BIGINT( 20 ) NOT NULL AUTO_INCREMENT");
    $this->db->query("ALTER TABLE `metadatavalue` CHANGE `metadatavalue_id` 
                      `metadatavalue_id` BIGINT( 20 ) NOT NULL AUTO_INCREMENT");
    }

    
  public function pgsql()
    {
    }
    
  public function postUpgrade()
    {
    }
}
?>


