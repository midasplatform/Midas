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

/** Upgrade the core to version 3.2.15. */
class Upgrade_3_2_15 extends MIDASUpgrade
{
    /** Upgrade a MySQL database. */
    public function mysql()
    {
    }

    /** Upgrade a PostgreSQL database. */
    public function pgsql()
    {
        $this->db->query('ALTER TABLE license ALTER fulltext DROP DEFAULT;');
        $this->db->query('ALTER TABLE license ALTER name DROP DEFAULT;');
        $this->db->query('ALTER TABLE errorlog RENAME errorlog_id_id TO errorlog_id;');
        $this->db->query('DROP INDEX IF EXISTS item2folder_idx_folder_id;');
        $this->db->query('CREATE INDEX item2folder_idx_folder_id ON item2folder (folder_id);');
    }
}
