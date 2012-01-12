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

abstract class Scheduler_JobModelBase extends Scheduler_AppModel
{
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'scheduler_job';
    $this->_key = 'job_id';
    $this->_daoName = 'JobDao';

    $this->_mainData= array(
        'job_id'=>  array('type'=>MIDAS_DATA),
        'task'=>  array('type'=>MIDAS_DATA),
        'run_only_once'=>  array('type'=>MIDAS_DATA),
        'fire_time'=>  array('type'=>MIDAS_DATA),
        'time_last_fired'=>  array('type'=>MIDAS_DATA),
        'time_interval'=>  array('type'=>MIDAS_DATA),
        'priority'=>  array('type'=>MIDAS_DATA),
        'status'=>  array('type'=>MIDAS_DATA),
        'params'=>  array('type'=>MIDAS_DATA),
        'creator_id' =>  array('type' => MIDAS_DATA),
        'logs' => array('type' => MIDAS_ONE_TO_MANY, 'model' => 'JobLog', 'module' => 'scheduler', 'parent_column' => 'job_id', 'child_column' => 'job_id'),
        'creator' =>  array('type' => MIDAS_MANY_TO_ONE, 'model' => 'User', 'parent_column' => 'creator_id', 'child_column' => 'user_id'),
        );
    $this->initialize(); // required
    } // end __construct()

  public abstract function getJobsByTask($task);
  public abstract function getJobsToRun();
  public abstract function getFutureScheduledJobs();

  /** get server load*/
  protected function getServerLoad()
    {
    if(isset($this->load))
      {
      return $this->load;
      }
    $os = strtolower(PHP_OS);
    if(strpos($os, "win") === false)
      {
      $this->load = sys_getloadavg();
      return $this->load;
      }
    else
      {
      ob_start();
      passthru('typeperf -sc 1 "\processor(_total)\% processor time"',$status);
      $content = ob_get_contents();
      ob_end_clean();
      if($status === 0)
        {
        if(preg_match("/\,\"([0-9]+\.[0-9]+)\"/",$content,$load))
          {
          $this->load = $load;
          return $load;
          }
        }
      }
    $this->load = array();
    return array();
  }
} // end class AssetstoreModelBase
?>