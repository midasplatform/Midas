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

/** Upgrade the scheduler module to version 1.1.0. */
class Scheduler_Upgrade_1_1_0 extends MIDASUpgrade
{
    /** Upgrade a MySQL database. */
    public function mysql()
    {
        $this->db->query("DROP TABLE IF EXISTS `scheduler_execution_state`;");
        $this->db->query("DROP TABLE IF EXISTS `scheduler_execution`;");
        $this->db->query("DROP TABLE IF EXISTS `scheduler_node_connection`;");
        $this->db->query("DROP TABLE IF EXISTS `scheduler_node`;");
        $this->db->query("DROP TABLE IF EXISTS `scheduler_variable_handler`;");
        $this->db->query("DROP TABLE IF EXISTS `scheduler_workflow`;");
    }

    /** Upgrade a PostgreSQL database. */
    public function pgsql()
    {
        $this->db->query("DROP INDEX IF EXISTS scheduler_execution_execution_parent;");
        $this->db->query("DROP INDEX IF EXISTS scheduler_node_workflow_id;");
        $this->db->query("DROP TABLE IF EXISTS scheduler_execution_state;");
        $this->db->query("DROP TABLE IF EXISTS scheduler_execution;");
        $this->db->query("DROP TABLE IF EXISTS scheduler_node_connection;");
        $this->db->query("DROP TABLE IF EXISTS scheduler_node;");
        $this->db->query("DROP TABLE IF EXISTS scheduler_variable_handler;");
        $this->db->query("DROP TABLE IF EXISTS scheduler_workflow;");
    }
}
