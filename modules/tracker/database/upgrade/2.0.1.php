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

/** Upgrade the tracker module to version 2.0.1 */
class Tracker_Upgrade_2_0_1 extends MIDASUpgrade
{
    /** Upgrade a MySQL database. */
    public function mysql()
    {
        $this->db->query(
            'CREATE TABLE IF NOT EXISTS `tracker_aggregate_metric_notification` ('.
            '    `aggregate_metric_notification_id` bigint(20) NOT NULL AUTO_INCREMENT,'.
            '    `aggregate_metric_spec_id` bigint(20) NOT NULL,'.
            "    `branch` varchar(255) NOT NULL DEFAULT '',".
            '    `value` double,'.
            "    `comparison` varchar(2) NOT NULL DEFAULT '',".
            '    PRIMARY KEY (`aggregate_metric_notification_id`),'.
            '    KEY (`aggregate_metric_spec_id`),'.
            '    KEY (`branch`)'.
            ') DEFAULT CHARSET=utf8;'
        );

        $this->db->query(
            'CREATE TABLE IF NOT EXISTS `tracker_user2aggregate_metric_notification` ('.
            '    `user_id` bigint(20) NOT NULL,'.
            '    `aggregate_metric_notification_id` bigint(20) NOT NULL,'.
            '    PRIMARY KEY (`user_id`, `aggregate_metric_notification_id`)'.
            ') DEFAULT CHARSET=utf8;'
        );

        // Migrate AMS (comparison, value, branch) to tracker_aggregate_metric_notification.
        $this->db->query(
            'INSERT INTO `tracker_aggregate_metric_notification` '.
            '   (`aggregate_metric_spec_id`, `branch`, `value`, `comparison`) '.
            'SELECT '.
            '   `aggregate_metric_spec_id`, `branch`, `value`, `comparison` '.
            'from `tracker_aggregate_metric_spec`;'
        );

        // Migrate notified users.
        $this->db->query(
            'INSERT INTO `tracker_user2aggregate_metric_notification` '.
            '   (`user_id`, `aggregate_metric_notification_id`) '.
            'SELECT '.
            '   `user_id`, `aggregate_metric_notification_id` '.
            'FROM `tracker_aggregate_metric_notification` AS `amn`, `tracker_user2aggregate_metric_spec` AS `u2ams` WHERE '.
            '   `amn`.`aggregate_metric_spec_id` = `u2ams`.`aggregate_metric_spec_id`;'
        );

        // Drop migrated columns and tables.
        $this->db->query(
            'ALTER TABLE `tracker_aggregate_metric_spec` '.
            '   DROP COLUMN `branch`, '.
            '   DROP COLUMN `comparison`, '.
            '   DROP COLUMN `value`;'
        );

        $this->db->query('DROP TABLE `tracker_user2aggregate_metric_spec`;');
    }
}
