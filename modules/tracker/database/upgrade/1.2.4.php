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

/** Upgrade the tracker module to version 1.2.4. */
class Tracker_Upgrade_1_2_4 extends MIDASUpgrade
{
    /** Upgrade a MySQL database. */
    public function mysql()
    {
        $this->db->query(
            'CREATE TABLE IF NOT EXISTS `tracker_aggregate_metric` ('.
            '    `aggregate_metric_id` bigint(20) NOT NULL AUTO_INCREMENT,'.
            '    `aggregate_metric_spec_id` bigint(20) NOT NULL,'.
            '    `submission_id` bigint(20) NOT NULL,'.
            '    `value` double,'.
            '    PRIMARY KEY (`aggregate_metric_id`),'.
            '    KEY (`aggregate_metric_spec_id`),'.
            '    KEY (`submission_id`)'.
            ') DEFAULT CHARSET=utf8;'
        );
        $this->db->query(
            'CREATE TABLE IF NOT EXISTS `tracker_aggregate_metric_spec` ('.
            '    `aggregate_metric_spec_id` bigint(20) NOT NULL AUTO_INCREMENT,'.
            '    `producer_id` bigint(20) NOT NULL,'.
            "    `branch` varchar(255) NOT NULL DEFAULT '',".
            "    `name` varchar(255) NOT NULL DEFAULT '',".
            "    `description` varchar(255) NOT NULL DEFAULT '',".
            "    `spec` text NOT NULL DEFAULT '',".
            '    `value` double,'.
            "    `comparison` varchar(2) NOT NULL DEFAULT '',".
            '    PRIMARY KEY (`aggregate_metric_spec_id`),'.
            '    KEY (`producer_id`),'.
            '    KEY (`branch`)'.
            ') DEFAULT CHARSET=utf8;'
        );
        $this->db->query(
            'CREATE TABLE IF NOT EXISTS `tracker_user2aggregate_metric_spec` ('.
            '    `user_id` bigint(20) NOT NULL,'.
            '    `aggregate_metric_spec_id` bigint(20) NOT NULL,'.
            '    PRIMARY KEY (`user_id`, `aggregate_metric_spec_id`)'.
            ') DEFAULT CHARSET=utf8;'
        );
    }
}
