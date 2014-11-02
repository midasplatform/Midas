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

/** Upgrade the core to version 3.0.1. */
class Upgrade_3_0_1 extends MIDASUpgrade
{
    /** Upgrade a MySQL database. */
    public function mysql()
    {
        $sql = "ALTER TABLE folderpolicygroup ADD COLUMN date timestamp DEFAULT CURRENT_TIMESTAMP(); ";
        $this->db->query($sql);
        $sql = "ALTER TABLE folderpolicyuser ADD COLUMN date timestamp DEFAULT CURRENT_TIMESTAMP(); ";
        $this->db->query($sql);
        $sql = "ALTER TABLE itempolicygroup ADD COLUMN date timestamp DEFAULT CURRENT_TIMESTAMP(); ";
        $this->db->query($sql);
        $sql = "ALTER TABLE itempolicyuser ADD COLUMN date timestamp DEFAULT CURRENT_TIMESTAMP(); ";
        $this->db->query($sql);
        $sql = "ALTER TABLE feedpolicygroup ADD COLUMN date timestamp DEFAULT CURRENT_TIMESTAMP(); ";
        $this->db->query($sql);
        $sql = "ALTER TABLE feedpolicyuser ADD COLUMN date timestamp DEFAULT CURRENT_TIMESTAMP(); ";
        $this->db->query($sql);
    }

    /** Upgrade a PostgreSQL database. */
    public function pgsql()
    {
        $sql = "ALTER TABLE folderpolicygroup ADD COLUMN date TIMESTAMP DEFAULT CURRENT_TIMESTAMP; ";
        $this->db->query($sql);
        $sql = "ALTER TABLE folderpolicyuser ADD COLUMN date TIMESTAMP DEFAULT CURRENT_TIMESTAMP; ";
        $this->db->query($sql);
        $sql = "ALTER TABLE itempolicygroup ADD COLUMN date TIMESTAMP DEFAULT CURRENT_TIMESTAMP; ";
        $this->db->query($sql);
        $sql = "ALTER TABLE itempolicyuser ADD COLUMN date TIMESTAMP DEFAULT CURRENT_TIMESTAMP; ";
        $this->db->query($sql);
        $sql = "ALTER TABLE feedpolicygroup ADD COLUMN date TIMESTAMP DEFAULT CURRENT_TIMESTAMP; ";
        $this->db->query($sql);
        $sql = "ALTER TABLE feedpolicyuser ADD COLUMN date TIMESTAMP DEFAULT CURRENT_TIMESTAMP; ";
        $this->db->query($sql);
    }
}
