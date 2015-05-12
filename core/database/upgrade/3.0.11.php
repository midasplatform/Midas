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

/** Upgrade the core to version 3.0.11. */
class Upgrade_3_0_11 extends MIDASUpgrade
{
    /** Upgrade a MySQL database. */
    public function mysql()
    {
        $this->db->query('
            CREATE TABLE IF NOT EXISTS `communityinvitation` (
                `communityinvitation_id` bigint(20) NOT NULL AUTO_INCREMENT,
                `community_id` bigint(20),
                `user_id` bigint(20),
                PRIMARY KEY (`communityinvitation_id`)
            ) DEFAULT CHARSET=utf8;
        ');
    }

    /** Upgrade a PostgreSQL database. */
    public function pgsql()
    {
        $this->db->query('
            CREATE TABLE  communityinvitation (
                communityinvitation_id  serial  PRIMARY KEY,
                community_id bigint,
                user_id bigint
            );
        ');
    }
}
