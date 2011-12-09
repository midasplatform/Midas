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

/** Base class for the job log model */
class Scheduler_JobLogModelBase extends Scheduler_AppModel
{
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'scheduler_job_log';
    $this->_daoName = 'JobLogDao';
    $this->_key = 'log_id';

    $this->_mainData = array(
      'log_id' => array('type' => MIDAS_DATA),
      'job_id' => array('type' => MIDAS_DATA),
      'date' => array('type' => MIDAS_DATA),
      'log' => array('type' => MIDAS_DATA),
      'job' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Job','module' => 'scheduler', 'parent_column' => 'job_id', 'child_column' => 'job_id')
      );
    $this->initialize(); // required
    } // end __construct()


  /** save logs */
  public function saveLog($jobDao, $text)
    {
    $this->loadDaoClass('JobLogDao', 'scheduler');
    $joblog = new Scheduler_JobLogDao();
    $joblog->setJobId($jobDao->getKey());
    $joblog->setDate(date('c'));
    $joblog->setLog($text);

    $this->save($joblog);
    }
} // end class JobLogModelBase
?>
