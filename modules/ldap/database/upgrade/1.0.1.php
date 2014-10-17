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
 * Add the ldap_user table for storing ldap users
 */
class Ldap_Upgrade_1_0_1 extends MIDASUpgrade
{
    /** Mysql upgrade */
    public function mysql()
    {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `ldap_user` (
                      `ldap_user_id` bigint(20) NOT NULL AUTO_INCREMENT,
                      `user_id` bigint(20) NOT NULL,
                      `login` varchar(255) NOT NULL,
                      PRIMARY KEY (`ldap_user_id`),
                      KEY `login` (`login`)
                      )"
        );
    }

    /** Pgsql upgrade */
    public function pgsql()
    {
        $this->db->query(
            "CREATE TABLE ldap_user (
                      ldap_user_id serial PRIMARY KEY,
                      user_id bigint NOT NULL,
                      login character varying(255) NOT NULL)"
        );

        $this->db->query("CREATE INDEX ldap_user_login_idx ON ldap_user (login)");
    }
}
