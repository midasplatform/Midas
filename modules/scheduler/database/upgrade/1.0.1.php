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

class Scheduler_Upgrade_1_0_1 extends MIDASUpgrade
  {
  public function mysql()
    {
    $sql = "CREATE TABLE IF NOT EXISTS `scheduler_job_log` (
        `log_id` bigint(20) NOT NULL,
        `job_id` bigint(20),
        `date` timestamp,
        `log` text,
        PRIMARY KEY (`log_id`)
      ) DEFAULT CHARSET=utf8";
    $this->db->query($sql);
    }
  }
