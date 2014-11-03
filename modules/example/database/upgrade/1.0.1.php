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

/** Upgrade the example module to version 1.0.1. */
class Example_Upgrade_1_0_1 extends MIDASUpgrade
{
    /** Upgrade a MySQL database. */
    public function mysql()
    {
        $this->db->query("ALTER TABLE `example_wallet` ADD COLUMN `credit_card_count` int(10) NOT NULL DEFAULT '0';");
    }

    /** Upgrade a PostgreSQL database. */
    public function pgsql()
    {
        $this->db->query("ALTER TABLE example_wallet ADD COLUMN credit_card_count integer NOT NULL DEFAULT 0;");
    }

    /** Upgrade a SQLite database. */
    public function sqlite()
    {
        $this->db->query("ALTER TABLE example_wallet ADD COLUMN credit_card_count INTEGER NOT NULL DEFAULT 0;");
    }
}
