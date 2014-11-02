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

/** Upgrade the core to version 3.2.10. Add the newuserinvite table. */
class Upgrade_3_2_10 extends MIDASUpgrade
{
    /** Upgrade a MySQL database. */
    public function mysql()
    {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `newuserinvitation` (
      `newuserinvitation_id` bigint(20) NOT NULL AUTO_INCREMENT,
      `auth_key` varchar(255) NOT NULL,
      `email` varchar(255) NOT NULL,
      `inviter_id` bigint(20) NOT NULL,
      `community_id` bigint(20) NOT NULL,
      `group_id` bigint(20) NOT NULL,
      `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`newuserinvitation_id`)
      )"
        );
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `pendinguser` (
      `pendinguser_id` bigint(20) NOT NULL AUTO_INCREMENT,
      `auth_key` varchar(255) NOT NULL,
      `email` varchar(255) NOT NULL,
      `password` varchar(100) NOT NULL,
      `firstname` varchar(255) NOT NULL,
      `lastname` varchar(255) NOT NULL,
      `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`pendinguser_id`)
      )"
        );
        $this->db->query("ALTER TABLE `communityinvitation` ADD COLUMN `group_id` bigint(20) NULL DEFAULT NULL");
    }

    /** Upgrade a PostgreSQL database. */
    public function pgsql()
    {
        $this->db->query(
            "CREATE TABLE newuserinvitation (
      newuserinvitation_id serial PRIMARY KEY,
      auth_key character varying(255) NOT NULL,
      email character varying(255) NOT NULL,
      inviter_id bigint NOT NULL,
      community_id bigint NOT NULL,
      group_id bigint NOT NULL,
      date_creation timestamp without time zone NOT NULL DEFAULT now()
      )"
        );
        $this->db->query(
            "CREATE TABLE pendinguser (
      pendinguser_id serial PRIMARY KEY,
      auth_key character varying(255) NOT NULL,
      email character varying(255) NOT NULL,
      password character varying(100) NOT NULL,
      firstname character varying(255) NOT NULL,
      lastname character varying(255) NOT NULL,
      date_creation timestamp without time zone NOT NULL DEFAULT now()
      )"
        );
        $this->db->query("ALTER TABLE communityinvitation ADD COLUMN group_id bigint NULL DEFAULT NULL");
    }
}
