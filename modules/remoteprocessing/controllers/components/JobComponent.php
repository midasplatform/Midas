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

/** Web API Authentication Component */
class Remoteprocessing_JobComponent extends AppComponent
{

  /** Constructor */
  function __construct()
    {
    }

  /** Init Remote Params */
  public function initJobParameters($resultCallback, $inputArray = array(), $ouputArray = array(), $additionalParams = array())
    {
    $return = array();
    $notifications = Zend_Registry::get('notifier')->getNotifications();
    if(!isset($notifications[$resultCallback]))
      {
      throw new Zend_Exception("Unable to find callback ".$resultCallback);
      }
    if(!is_array($inputArray) || !is_array($ouputArray) || !is_array($additionalParams))
      {
      throw new Zend_Exception("Error params");
      }

    $return = $additionalParams;
    $return['resultCallback'] = $resultCallback;
    $return['input'] = array();
    $return['output'] = array();

    foreach($inputArray as $input)
      {
      if(!$input instanceof ItemDao)
        {
        throw new Zend_Exception("Error params. Shoud be an itemdao");
        }
      $return['input'][] = $input->getKey();

      }

    foreach($ouputArray as $output)
      {
      if(!is_string($output))
        {
        throw new Zend_Exception("Error params. Shoud be a string");
        }
      $return['output'][] = $output;
      }

    return $return;
    }


  /** init Job*/
  public function scheduleJob($parameters, $script, $os = MIDAS_REMOTEPROCESSING_OS_WINDOWS, $fire_time = false, $time_interval = false, $only_once = true, $condition = '')
    {
    $scriptParams['script'] = $script;
    $scriptParams['os'] = $os;
    $scriptParams['condition'] = $condition;
    $scriptParams['params'] = $parameters;

    $scheduleParams['task'] = 'TASK_REMOTEPROCESSING_ADD_JOB';
    $scheduleParams['params'] = $scriptParams;
    if(is_string($fire_time))
      {
      $scheduleParams['fire_time'] = $fire_time;
      }

    if(!$only_once && $time_interval !== false)
      {
      $scheduleParams['run_only_once'] = false;
      $scheduleParams['time_interval'] = $time_interval;
      }

    Zend_Registry::get('notifier')->callback("CALLBACK_SCHEDULER_SCHEDULE_TASK", $scheduleParams);
    }

}
