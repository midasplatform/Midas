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

/** Upgrade the tracker module to version 2.0.4 */
class Tracker_Upgrade_2_0_4 extends MIDASUpgrade
{
    /** Upgrade a MySQL database. */
    public function mysql()
    {
        $this->db->query(
            'ALTER TABLE `tracker_producer` '.
            '   ADD COLUMN `grid_across_metric_groups` tinyint(4) NOT NULL DEFAULT 0,'.
            '   ADD COLUMN `histogram_number_of_bins` int(11) NOT NULL DEFAULT 10,'.
            '   ADD COLUMN `producer_definition` text;'
        );
        $this->db->query(
            'ALTER TABLE `tracker_trend_threshold` '.
            '   ADD COLUMN `min` double,'.
            '   ADD COLUMN `lower_is_better` tinyint(4) NOT NULL DEFAULT 0;'
        );
    }
}
