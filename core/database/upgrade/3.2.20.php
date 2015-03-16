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

/** Upgrade the core to version 3.2.20. */
class Upgrade_3_2_20 extends MIDASUpgrade
{
    /** Upgrade a MySQL database. */
    public function mysql()
    {
    $this->db->query("ALTER TABLE `folder` CHANGE `teaser` `teaser` varchar(255) NULL DEFAULT '';");
    $this->db->query("ALTER TABLE `item` CHANGE `name` `name` varchar(255) NOT NULL;");
    $this->db->query("ALTER TABLE `token` CHANGE `token` `token` varchar(64) NOT NULL;");
    $this->db->query("ALTER TABLE `user` CHANGE COLUMN `biography` `biography` TEXT;");
    $this->db->query("ALTER TABLE `userapi` CHANGE `apikey` `apikey` varchar(64) NOT NULL, CHANGE `application_name` `application_name` varchar(255) NOT NULL;");
    }

    /** Upgrade a PostgreSQL database. */
    public function pgsql()
    {
    $this->db->query("ALTER TABLE folder ALTER teaser TYPE character varying(256);");
    $this->db->query("ALTER TABLE item ALTER name TYPE character varying(256);");
    $this->db->query("ALTER TABLE token ALTER token TYPE character varying(64);");
    $this->db->query("ALTER TABLE newuserinvitation ALTER auth_key TYPE character varying(256), ALTER email TYPE character varying(256);");
    $this->db->query("ALTER TABLE pendinguser ALTER auth_key TYPE character varying(256), ALTER email TYPE character varying(256), ALTER firstname TYPE character varying(256), ALTER lastname TYPE character varying(256);");
    $this->db->query("ALTER TABLE \"user\" ALTER website TYPE character varying(256), ALTER biography TYPE text, ALTER biography DROP DEFAULT;");
    $this->db->query("ALTER TABLE userapi ALTER apikey TYPE character varying(64);");
    }
}
