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
 * Upgrade the tracker module to version 1.2.0.
 *
 * @package Modules\Tracker\Database
 */
class Tracker_Upgrade_1_2_0 extends MIDASUpgrade
{

    /** Upgrade a MySQL database. */
    public function mysql()
    {
        $this->db->query("ALTER TABLE `tracker_scalar` ADD COLUMN `submission_id` bigint(20) NOT NULL DEFAULT '-1';");
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `tracker_submission` (" .
            "    PRIMARY KEY (`submission_id`)," .
            "    `submission_id` bigint(20) NOT NULL AUTO_INCREMENT,".
            "    `producer_id` bigint(20) NOT NULL,".
            "    `name` varchar(255) NOT NULL DEFAULT '',".
            "    `uuid` varchar(255) NOT NULL DEFAULT '',".
            "    `submit_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,".
            "    UNIQUE KEY (`uuid`)".
            ") DEFAULT CHARSET=utf8;");
    }

    /** Upgrade a PostgreSQL database. */
    public function pgsql()
    {
        $this->db->query("ALTER TABLE tracker_scalar ADD COLUMN submission_id bigint NOT NULL DEFAULT -1::bigint;");
        $this->db->query(
            'CREATE TABLE IF NOT EXISTS "tracker_submission" ('.
            '    "submission_id" serial PRIMARY KEY,'.
            '    "producer_id" bigint,'.
            '    "name" character varying(255) NOT NULL DEFAULT \'\'::character varying,'.
            '    "uuid" character varying(255) NOT NULL DEFAULT \'\'::character varying,'.
            '    "submit_time" timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP);'
        );
        $this->db->query('CREATE UNIQUE INDEX "tracker_submission_uuid" ON "tracker_submission" ("uuid");');
        $this->db->query('CREATE INDEX "tracker_submission_submit_time" ON "tracker_submission" ("submit_time");');
    }

}
