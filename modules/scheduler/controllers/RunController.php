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

/**
 * TaskController
 * 
 */
class Scheduler_RunController extends Scheduler_AppController
{
  public $_moduleModels=array('Job');
  public $_components=array('Json');

  /**
   * @method initAction()
   *  Index Action (first action when we access the application)
   */
  function init()
    {      

    } // end method indexAction

  function indexAction()
    {
    $this->disableLayout();
    $this->disableView();
    $jobs = $this->Scheduler_Job->getJobsToRun();
    $modules = Zend_Registry::get('notifier')->modules;
    $tasks = Zend_Registry::get('notifier')->tasks;
    foreach($jobs as $job)
      {
      $job->setStatus(SCHEDULER_JOB_STATUS_STARTED);
      if($job->getRunOnlyOnce() == 0)
        {
        $firetime = strtotime($job->getFireTime()) + $job->getTimeInterval();
        $job->setFireTime(date('c', $firetime));
        }
      $job->setTimeLastFired(date('c'));
      $this->Scheduler_Job->save($job);
      try
        {
        if(!isset($tasks[$job->getTask()]))
          {
          throw new Zend_Exception('Unable to find task');
          }
        call_user_func(array($modules[$tasks[$job->getTask()]['module']],$tasks[$job->getTask()]['method']), JsonComponent::decode($job->getParams()));
        }
      catch (Zend_Exception $exc)
        {
        $job->setStatus(SCHEDULER_JOB_STATUS_FAILED);
        $this->Scheduler_Job->save($job);
        continue;
        }
      if($job->getRunOnlyOnce() == 0)
        {
        $job->setStatus(SCHEDULER_JOB_STATUS_TORUN);
        }
      else
        {
        $job->setStatus(SCHEDULER_JOB_STATUS_DONE);
        }
      $this->Scheduler_Job->save($job);
      }
    } 
    
}//end class