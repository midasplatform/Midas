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

/** Upgrade the tracker module to version 1.2.2. */
class Tracker_Upgrade_1_2_2 extends MIDASUpgrade
{
    /** Upgrade a MySQL database. */
    public function mysql()
    {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `tracker_param` (
                `param_id` bigint(20) NOT NULL AUTO_INCREMENT,
                `scalar_id` bigint(20) NOT NULL,
                `param_name` varchar(255) NOT NULL,
                `param_type` enum('text', 'numeric') NOT NULL,
                `text_value` text,
                `numeric_value` double,
                PRIMARY KEY (`param_id`),
                KEY (`param_name`)
            ) DEFAULT CHARSET=utf8;");
    }

    /** Upgrade a PostgreSQL database. */
    public function pgsql()
    {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS tracker_param (
                param_id serial PRIMARY KEY,
                scalar_id bigint NOT NULL,
                param_name character varying(255) NOT NULL,
                param_type text CHECK (param_type in ('text', 'numeric')),
                text_value text,
                numeric_value double precision);"
        );
        $this->db->query('CREATE INDEX tracker_param_param_name_idx ON tracker_param (param_name);');
    }
}
