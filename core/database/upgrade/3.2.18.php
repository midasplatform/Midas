<?php
/*=========================================================================
 Midas Server
 Copyright Kitware SAS, 26 rue Louis Guérin, 69100 Villeurbanne, France.
 All rights reserved.
 For more information visit http://www.kitware.com/.

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

/** Upgrade the core to version 3.2.18. */
class Upgrade_3_2_18 extends MIDASUpgrade
{

    /** Upgrade a MySQL database. */
    public function mysql()
    {
        $this->db->query("ALTER TABLE `folder` DROP KEY `left_indice`;");
        $this->db->query("ALTER TABLE `folder` DROP KEY `right_indice`;");
        $this->db->query("ALTER TABLE `folder` CHANGE  `left_indice` `left_index` bigint(20) NOT NULL;");
        $this->db->query("ALTER TABLE `folder` CHANGE  `right_indice` `right_index` bigint(20) NOT NULL;");
        $this->db->query("ALTER TABLE `folder` ADD KEY (`left_index`);");
        $this->db->query("ALTER TABLE `folder` ADD KEY (`right_index`);");
        $this->db->query("ALTER TABLE `feed` CHANGE  `ressource` `resource` varchar(255) NOT NULL;");
    }

    /** Upgrade a PostgreSQL database. */
    public function pgsql()
    {
        $this->db->query("DROP INDEX IF EXISTS folder_idx_left_indice;");
        $this->db->query("DROP INDEX IF EXISTS folder_idx_right_indice;");
        $this->db->query("ALTER TABLE folder RENAME left_indice TO left_index;");
        $this->db->query("ALTER TABLE folder RENAME right_indice TO right_index;");
        $this->db->query("CREATE INDEX folder_idx_left_index ON folder (left_index);");
        $this->db->query("CREATE INDEX folder_idx_right_index ON folder (right_index);");
        $this->db->query("ALTER TABLE feed RENAME ressource TO resource;");
    }
}
