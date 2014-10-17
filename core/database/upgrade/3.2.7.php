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
 * Upgrade 3.2.7 adds the progress table
 */
class Upgrade_3_2_7 extends MIDASUpgrade
{
    public function mysql()
    {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `progress` (
      `progress_id` bigint(20) NOT NULL AUTO_INCREMENT,
      `message` TEXT NOT NULL,
      `current` bigint(20) NOT NULL,
      `maximum` bigint(20) NOT NULL,
      `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `last_update` timestamp NOT NULL,
      PRIMARY KEY (`progress_id`)
      )"
        );
    }

    public function pgsql()
    {
        $this->db->query(
            "CREATE TABLE progress (
      progress_id serial PRIMARY KEY,
      message TEXT NOT NULL,
      current bigint NOT NULL,
      maximum bigint NOT NULL,
      date_creation timestamp without time zone NOT NULL DEFAULT now(),
      last_update timestamp without time zone NOT NULL
      )"
        );
    }
}
