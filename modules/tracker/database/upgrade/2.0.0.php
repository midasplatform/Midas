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

/**
 * Upgrade the tracker module to version 2.0.0.
 */
class Tracker_Upgrade_2_0_0 extends MIDASUpgrade
{
    /** Upgrade a MySQL database. */
    public function mysql()
    {
        // Create new table to relate items to submissions
        $this->db->query(
           'CREATE TABLE IF NOT EXISTS `tracker_submission2item` ('.
           '`submission_id` bigint(20) NOT NULL,'.
           '`item_id` bigint(20) NOT NULL,'.
           '`label` varchar(255) NOT NULL,'.
           'KEY (`submission_id`)'.
           ') DEFAULT CHARSET=utf8;'
        );

        // Create new table to relate params to submissions. We will rename this later
        // TODO(cpatrick): rename members in model classes
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `tracker_submissionparam` (
                `param_id` bigint(20) NOT NULL AUTO_INCREMENT,
                `submission_id` bigint(20) NOT NULL,
                `param_name` varchar(255) NOT NULL,
                `param_type` enum('text', 'numeric') NOT NULL,
                `text_value` text,
                `numeric_value` double,
                PRIMARY KEY (`param_id`),
                KEY (`submission_id`),
                KEY (`param_name`)
            ) DEFAULT CHARSET=utf8;");

        // Add columns to submission (that were formerly on scalar)
        $this->db->query('ALTER TABLE tracker_submission ADD COLUMN `producer_revision` VARCHAR(255);');
        $this->db->query('ALTER TABLE tracker_submission ADD COLUMN `user_id` bigint(20) NOT NULL DEFAULT \'-1\';');
        $this->db->query('ALTER TABLE tracker_submission ADD COLUMN `official` tinyint(4) NOT NULL DEFAULT \'1\';');
        $this->db->query('ALTER TABLE tracker_submission ADD COLUMN `build_results_url` text NOT NULL;');
        $this->db->query('ALTER TABLE tracker_submission ADD COLUMN `branch` varchar(255) NOT NULL DEFAULT \'\';');
        $this->db->query('ALTER TABLE tracker_submission ADD COLUMN `extra_urls` text;');
        $this->db->query('ALTER TABLE tracker_submission ADD COLUMN `reproduction_command` text;');

        // Create indices on submission
        $this->db->query('ALTER TABLE tracker_submission ADD KEY (`user_id`);');
        $this->db->query('ALTER TABLE tracker_submission ADD KEY(`submit_time`);');
        $this->db->query('ALTER TABLE tracker_submission ADD KEY (`branch`);');


        // TODO(cpatrick): Do grouping of scalars to create submissions.
        // This is done by running the stored procedure in ../mysql/create_submissions.sql

        // TODO(cpatrick): Move params to submission.
        // This is done by running the stored procedure in ../mysql/migrate_params.sql. This assumes create_submissions
        // has been run.

        // Drop old param table
        $this->db->query('DROP TABLE IF EXISTS tracker_param;');

        // Rename new param table.
        $this->db->query('RENAME TABLE tracker_submissionparam TO tracker_param;');

        // TODO(cpatrick): Move values from scalar2item to submission2item
        // This is done by running the stored procedure in ../mysql/migrate_items_to_submissions.sql

        // TODO(cpatrick): Move values from scalars to submissions.
        // This is done by running the stored procedure in ../mysql/scalar_to_submission.sql

        // Delete old values from scalars
        $this->db->query('ALTER TABLE tracker_scalar DROP COLUMN `producer_revision`;');
        $this->db->query('ALTER TABLE tracker_scalar DROP COLUMN `user_id`;');
        $this->db->query('ALTER TABLE tracker_scalar DROP COLUMN `official`;');
        $this->db->query('ALTER TABLE tracker_scalar DROP COLUMN `build_results_url`;');
        $this->db->query('ALTER TABLE tracker_scalar DROP COLUMN `branch`;');
        $this->db->query('ALTER TABLE tracker_scalar DROP COLUMN `extra_urls`;');
        $this->db->query('ALTER TABLE tracker_scalar DROP COLUMN `reproduction_command`;');
        $this->db->query('ALTER TABLE tracker_scalar DROP COLUMN `submit_time`;');
        $this->db->query('DROP TABLE IF EXISTS `tracker_scalar2item`;');

    }

    /** Upgrade a PostgreSQL database. */
    public function pgsql()
    {
        $this->db->query('');
    }
}
