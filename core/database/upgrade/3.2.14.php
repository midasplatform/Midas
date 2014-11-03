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
 * Upgrade the core to version 3.2.14. Fix bug #1001: folder modified time
 * changed incorrectly.
 */
class Upgrade_3_2_14 extends MIDASUpgrade
{
    /** Upgrade a MySQL database. */
    public function mysql()
    {
        // Remove the "on update current timestamp" qualifier from the date_update column.
        $this->db->query("ALTER TABLE `folder` CHANGE `date_update` `date_update` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;");
        $this->db->query("ALTER TABLE `item` CHANGE `date_update` `date_update` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;");
        $this->db->query("ALTER TABLE `itemrevision` CHANGE `date` `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;");
        $this->db->query("ALTER TABLE `bitstream` CHANGE `date` `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;");
        $this->db->query("ALTER TABLE `feed` CHANGE `date` `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;");
    }
}
