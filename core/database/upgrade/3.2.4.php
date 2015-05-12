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

/**
 * Upgrade the core to version 3.2.4. Add a table that will allow an IP address
 * to be locked during download so that a single IP address cannot flood the
 * server with downloads.
 */
class Upgrade_3_2_4 extends MIDASUpgrade
{
    /** Upgrade a MySQL database. */
    public function mysql()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `activedownload` (
            `activedownload_id` bigint(20) NOT NULL AUTO_INCREMENT,
            `ip` varchar(100) NOT NULL DEFAULT '',
            `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `last_update` timestamp NOT NULL,
            PRIMARY KEY (`activedownload_id`),
            KEY (`ip`)
            ) DEFAULT CHARSET=utf8;
        ");
    }

    /** Upgrade a PostgreSQL database. */
    public function pgsql()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS activedownload (
            activedownload_id serial PRIMARY KEY,
            ip character varying(100) NOT NULL DEFAULT '',
            date_creation timestamp without time zone NOT NULL DEFAULT now(),
            last_update timestamp without time zone NOT NULL);
        ");
        $this->db->query('CREATE INDEX activedownload_idx_ip ON activedownload (ip);');
    }
}
