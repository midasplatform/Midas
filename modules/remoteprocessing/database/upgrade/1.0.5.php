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

class Remoteprocessing_Upgrade_1_0_5 extends MIDASUpgrade
{
  public function preUpgrade()
    {

    }

  public function mysql()
    {
    $sql = "CREATE TABLE IF NOT EXISTS `remoteprocessing_workflowdomain` (
        `workflowdomain_id` bigint(20) NOT NULL AUTO_INCREMENT,
        `creation_date` timestamp NULL DEFAULT NULL ,
        `name` varchar(512) NOT NULL,
        `uuid` varchar(255) NOT NULL,
        `description` text NOT NULL ,
        PRIMARY KEY (`workflowdomain_id`)
      );";
    $this->db->query($sql);
    $sql = "
      CREATE TABLE IF NOT EXISTS `remoteprocessing_workflowdomain_policygroup` (
        `workflowdomain_id` bigint(20) NOT NULL,
        `group_id` bigint(20) NOT NULL,
        `policy` tinyint(4) NOT NULL,
        `date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `workflowdomain_id` (`workflowdomain_id`,`group_id`)
      )  DEFAULT CHARSET=utf8;";
    $this->db->query($sql);
    $sql = "
      CREATE TABLE IF NOT EXISTS `remoteprocessing_workflowdomain_policyuser` (
          `workflowdomain_id` bigint(20) NOT NULL,
          `user_id` bigint(20) NOT NULL,
          `policy` tinyint(4) NOT NULL,
          `date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          UNIQUE KEY `workflowdomain_id` (`workflowdomain_id`,`user_id`)
        )  DEFAULT CHARSET=utf8;";
    $this->db->query($sql);

    $sql = "ALTER TABLE remoteprocessing_workflow ADD workflowdomain_id bigint(20) NOT NULL";
    $this->db->query($sql);
    }

  public function pgsql()
    {

    }

  public function postUpgrade()
    {
    }
}
?>
