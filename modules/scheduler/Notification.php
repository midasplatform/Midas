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
/** notification manager*/
class Scheduler_Notification extends MIDAS_Notification
  {
  public $_moduleModels=array('Job');
  public $_moduleDaos=array('Job');
  public $_components=array('Json');
  public $moduleName = 'scheduler';

  /** init notification process*/
  public function init()
    {
    $this->addTask("TASK_SCHEDULER_SCHEDULE_TASK", 'scheduleTask', "Schedule a task. Parameters: task, priority, params");
    $this->addCallBack('CALLBACK_SCHEDULER_SCHEDULE_TASK', 'scheduleTask');
    }//end init

  /** get Config Tabs */
  public function scheduleTask($params)
    {
    $tasks = Zend_Registry::get('notifier')->tasks;
    if(!isset($params['task']) || !isset($tasks[$params['task']]))
      {
      throw new Zend_Exception('Unable to identify task: '.$params['task']);
      }
    if(!isset($params['priority']))
      {
      $params['priority'] = MIDAS_EVENT_PRIORITY_NORMAL;
      }
    if(!isset($params['run_only_once']))
      {
      $params['run_only_once'] = true;
      }

    if(!isset($params['fire_time']))
      {
      $params['fire_time'] = date('c');
      }
    elseif(is_numeric($params['fire_time']))
      {
      $params['fire_time'] = date('c', $params['fire_time']);
      }
    else
      {
      $params['fire_time'] = $params['fire_time'];
      }

    if(!$params['run_only_once'])
      {
      if(!isset($params['time_interval']))
        {
        throw new Zend_Exception('Please set time interval');
        }
      }
    $job = new Scheduler_JobDao();
    $job->setTask($params['task']);
    $job->setPriority($params['priority']);
    $job->setRunOnlyOnce($params['run_only_once']);
    $job->setFireTime($params['fire_time']);
    if(!$params['run_only_once'])
      {
      $job->setTimeInterval($params['time_interval']);
      }
    if($this->logged)
      {
      $job->setCreatorId($this->userSession->Dao->getKey());
      }
    $job->setStatus(SCHEDULER_JOB_STATUS_TORUN);
    $job->setParams(JsonComponent::encode($params['params']));

    $this->Scheduler_Job->save($job);
    return;
    }
  } //end class
?>