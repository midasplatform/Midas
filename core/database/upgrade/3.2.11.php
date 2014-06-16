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

/**
 * Upgrade 3.2.11 removes the public folder and private folder columns
 * from community and user tables
 */
class Upgrade_3_2_11 extends MIDASUpgrade
  {
  public function preUpgrade()
    {
    }

  public function mysql()
    {
    $this->db->query("ALTER TABLE `community` DROP `publicfolder_id`");
    $this->db->query("ALTER TABLE `community` DROP `privatefolder_id`");
    $this->db->query("ALTER TABLE `user` DROP `publicfolder_id`");
    $this->db->query("ALTER TABLE `user` DROP `privatefolder_id`");
    }

  public function pgsql()
    {
    $this->db->query("ALTER TABLE community DROP COLUMN publicfolder_id");
    $this->db->query("ALTER TABLE community DROP COLUMN privatefolder_id");
    $this->db->query("ALTER TABLE \"user\" DROP COLUMN publicfolder_id");
    $this->db->query("ALTER TABLE \"user\" DROP COLUMN privatefolder_id");
    }

  public function postUpgrade()
    {
    }
  }
