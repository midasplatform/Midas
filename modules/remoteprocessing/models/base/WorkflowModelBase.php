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
/** Job Model*/
class Remoteprocessing_WorkflowModelBase extends Remoteprocessing_AppModel
{
  /** construct */
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'remoteprocessing_workflow';
    $this->_key = 'workflow_id';
    $this->_daoName = 'WorkflowDao';

    $this->_mainData = array(
        'workflow_id' =>  array('type' => MIDAS_DATA),
        'creation_date' =>  array('type' => MIDAS_DATA),
        'name' =>  array('type' => MIDAS_DATA),
        'uuid' =>  array('type' => MIDAS_DATA),
        'description' =>  array('type' => MIDAS_DATA),
        'workflowdomain_id' =>  array('type' => MIDAS_DATA),
        'domain' =>  array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Workflowdomain', 'module' => 'remoteprocessing', 'parent_column' => 'workflowdomain_id', 'child_column' => 'workflowdomain_id'),
        'jobs' =>  array('type' => MIDAS_MANY_TO_MANY, 'model' => 'Job', 'module' => 'remoteprocessing', 'table' => 'remoteprocessing_workflow2job', 'parent_column' => 'workflow_id', 'child_column' => 'job_id'),
        );
    $this->initialize(); // required
    } // end __construct()

  /** save */
  public function save($dao)
    {
    if(!isset($dao->uuid) || empty($dao->uuid))
      {
      $dao->setUuid(uniqid() . md5(mt_rand()));
      }
    $dao->setCreationDate(date('c'));
    parent::save($dao);
    }
} // end class AssetstoreModelBase