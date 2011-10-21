<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/

class Scheduler_JobModelBase extends Scheduler_AppModel
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
        'logs' => array('type' => MIDAS_ONE_TO_MANY, 'model' => 'JobLog', 'module' => 'scheduler', 'parent_column' => 'job_id', 'child_column' => 'job_id'),
        );
    $this->initialize(); // required
    } // end __construct()


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