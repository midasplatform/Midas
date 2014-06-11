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
 * Upgrade 3.2.9 adds pgsql indicies on policy tables
 */
class Upgrade_3_2_9 extends MIDASUpgrade
  {
  public function preUpgrade()
    {
    }

  public function mysql()
    {
    }

  public function pgsql()
    {
    $this->db->query("ALTER TABLE itempolicygroup ADD CONSTRAINT itempolicygroup_item_group UNIQUE (item_id, group_id)");
    $this->db->query("ALTER TABLE itempolicyuser ADD CONSTRAINT itempolicyuser_item_user UNIQUE (item_id, user_id)");
    $this->db->query("ALTER TABLE folderpolicygroup ADD CONSTRAINT folderpolicygroup_folder_group UNIQUE (folder_id, group_id)");
    $this->db->query("ALTER TABLE folderpolicyuser ADD CONSTRAINT folderpolicyuser_folder_user UNIQUE (folder_id, user_id)");
    }

  public function postUpgrade()
    {
    }
  }
