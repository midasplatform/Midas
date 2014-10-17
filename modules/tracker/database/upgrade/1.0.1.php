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
 * 1. Adds a user_id value to a scalar record indicating which user uploaded the scalar
 * 2. Adds a binary "official" flag to a scalar record indicating if it is an official or experimental submission
 * 3. Adds a user_id index to the tracker_scalar table
 */
class Tracker_Upgrade_1_0_1 extends MIDASUpgrade
{
    public function mysql()
    {
        $this->db->query("ALTER TABLE `tracker_scalar` ADD COLUMN `user_id` bigint(20) NOT NULL DEFAULT -1");
        $this->db->query("ALTER TABLE `tracker_scalar` ADD COLUMN `official` tinyint(4) NOT NULL DEFAULT 1");

        $this->db->query("ALTER TABLE `tracker_scalar` ADD KEY (`user_id`)");
    }

    public function pgsql()
    {
        $this->db->query("ALTER TABLE tracker_scalar ADD COLUMN user_id bigint NOT NULL DEFAULT -1");
        $this->db->query("ALTER TABLE tracker_scalar ADD COLUMN official smallint NOT NULL DEFAULT 1");

        $this->db->query("CREATE INDEX tracker_scalar_idx_user_id ON tracker_scalar (user_id)");
    }
}
