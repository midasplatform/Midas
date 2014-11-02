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
 * Upgrade the core to version 3.2.5. Add indexes for faster lookup of the
 * folder and item hierarchies.
 */
class Upgrade_3_2_5 extends MIDASUpgrade
{
    /** Upgrade a MySQL database. */
    public function mysql()
    {
        $this->db->query("ALTER TABLE `folder` ADD INDEX (`left_indice`)");
        $this->db->query("ALTER TABLE `folder` ADD INDEX (`right_indice`)");
        $this->db->query("ALTER TABLE `item2folder` ADD INDEX (`folder_id`)");
    }

    /** Upgrade a PostgreSQL database. */
    public function pgsql()
    {
        $this->db->query("CREATE INDEX folder_idx_parent_id ON folder (parent_id)");
        $this->db->query("CREATE INDEX folder_idx_left_indice ON folder (left_indice)");
        $this->db->query("CREATE INDEX folder_idx_right_indice ON folder (right_indice)");
        $this->db->query("CREATE INDEX item2folder_idx_folder_id ON folder (folder_id)");
    }
}
