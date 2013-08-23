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

/** Notify modules using this class*/
class MIDAS_Notifier
  {
  public $modules = array();
  public $tasks = array();
  private $tasksByModule = array();
  public $notifications = array();

  /** get Notification */
  public function getNotifications()
    {
    return $this->notifications;
    }

  /** get Tasks */
  public function getTasks()
    {
    return $this->tasks;
    }

  /** init the notifier*/
  public function __construct($logged, $session)
    {
    $modules = Zend_Registry::get('modulesEnable');
    foreach($modules as $module)
      {
      if(file_exists(BASE_PATH.'/modules/'.$module.'/Notification.php'))
        {
        require_once BASE_PATH.'/modules/'.$module.'/Notification.php';
        $name = ucfirst($module).'_Notification';
        if(!class_exists($name))
          {
          throw new Zend_Exception('Unable to find notification class: '.$name);
          }
        $this->modules[$module] = new $name();
        $this->modules[$module]->logged = $logged;
        $this->modules[$module]->userSession = $session;
        }
      if(file_exists(BASE_PATH.'/privateModules/'.$module.'/Notification.php'))
        {
        require_once BASE_PATH.'/privateModules/'.$module.'/Notification.php';
        $name = ucfirst($module).'_Notification';
        if(!class_exists($name))
          {
          throw new Zend_Exception('Unable to find notification class: '.$name);
          }
        $this->modules[$module] = new $name();
        $this->modules[$module]->logged = $logged;
        $this->modules[$module]->userSession = $session;
        }
      }

    if(file_exists(BASE_PATH.'/core/Notification.php'))
      {
      require_once BASE_PATH.'/core/Notification.php';
      $name = 'Notification';
      if(!class_exists($name))
        {
        throw new Zend_Exception('Unable to find notification class: '.$name);
        }
      $this->modules['core'] = new $name();
      $this->modules['core']->logged = $logged;
      $this->modules['core']->userSession = $session;
      }

    foreach($this->modules as $module => $notificationClass)
      {
      $tasks = $notificationClass->getTasks();
      foreach($tasks as $name => $task)
        {
        if(isset($this->tasks[$name]))
          {
          throw new Zend_Exception('Task already exits');
          }
        if(strpos($name, "TASK_") === false  || !is_string($name))
          {
          throw new Zend_Exception('Task name should be a string: TASK_MODULE_NAME');
          }
        $this->tasks[$name] = $task;
        $this->tasks[$name]['module'] = $module;

        $this->tasksByModule[$module] = $task;
        $this->tasksByModule[$module]['name'] = $name;
        }
      }

    foreach($this->modules as $module => $notificationClass)
      {
      $notifications = $notificationClass->getNotifications();
      foreach($notifications as $name => $notificationArray)
        {
        if(!isset($this->notifications[$name]))
          {
          $this->notifications[$name] = array();
          }

        foreach($notificationArray as $notification)
          {
          if($notification['type'] == 'callback')
            {
            if(strpos($name, "CALLBACK_") === false || !is_string($name))
              {
              throw new Zend_Exception('Callback name should be a string: CALLBACK_MODULE_NAME. Was '.$name);
              }
            }
          else
            {
            if(strpos($name, "EVENT_") === false  || !is_string($name))
              {
              throw new Zend_Exception('Event name should be a string: EVENT_MODULE_NAME. Was '.$name);
              }
            if(!isset($this->tasks[$notification['call']]))
              {
              throw new Zend_Exception('The task doesn\'t exit');
              }
            }
          $notification['module'] = $module;
          $this->notifications[$name][] = $notification;
          }
        }
      }
    }//end contruct()

  /**
   * notifyEvent is very similar to callback, with an important distinction.
   * If the scheduler module is enabled, the event will be scheduled to run asynchronously.
   * Otherwise, it will be called synchronously like a normal callback.
   * @param $name The name of the event. Must start with "EVENT_".
   * @param $params (Optional) Array of parameters to be passed to the registered handlers.
   * @param $moduleFilter (Optional) Only the listed modules will receive the notification.
   * @return null
   */
  public function notifyEvent($name, $params = null, $moduleFilter = array())
    {
    if(strpos($name, "EVENT_") === false || !is_string($name))
      {
      throw new Zend_Exception('Event name should be a string: EVENT_MODULE_NAME. Was '.$name);
      }
    if(!isset($this->notifications[$name]))
      {
      return;
      }
    if(is_string($moduleFilter))
      {
      $moduleFilter = array($moduleFilter);
      }
    foreach($this->notifications[$name] as $key => $notification)
      {
      $module = $this->modules[$notification['module']];
      if(empty($moduleFilter) || in_array($module, $moduleFilter))
        {
        $this->_setTask($notification['call'], $params, $notification['priority']);
        }
      }
    }//end notify

  /**
   * If the scheduler module is enabled, schedule the task for asynchronous execution.
   * Otherwise, run it now synchronously.
   */
  private function _setTask($name, $params, $priority)
    {
    $modules = Zend_Registry::get('modulesEnable');
    if(!isset($this->tasks[$name]))
      {
      return;
      }
    if(isset($this->modules['scheduler']))
      {
      $params = array('task' => $name, 'priority' => $priority, 'params' => $params, 'run_only_once' => true, 'fire_time' => date("Y-m-d H:i:s"));
      call_user_func(array($this->modules['scheduler'], $this->tasks['TASK_SCHEDULER_SCHEDULE_TASK']['method']), $params);
      }
    else
      {
      call_user_func(array($this->modules[$this->tasks[$name]['module']], $this->tasks[$name]['method']), $params);
      }
    }

  /**
   * Modules that have registered handlers for the specified callback will have their
   * handlers called by this function. Handlers will be executed synchronously and serially,
   * in the order that the enabled modules are listed in application.local.ini.
   * @param $name The name of the callback (must begin with "CALLBACK_")
   * @param $params (Optional) The array of parameters to pass to each registered handler
   * @param $moduleFilter (Optional) Only the listed modules will receive the notification.
   * @return An array mapping module names to the return values from their registered handlers for the callback.
   */
  public function callback($name, $params = null, $moduleFilter = array())
    {
    if(strpos($name, "CALLBACK_") === false || !is_string($name))
      {
      throw new Zend_Exception('Callback name should be a string: CALLBACK_MODULE_NAME. Was '.$name);
      }
    if(!isset($this->notifications[$name]))
      {
      return array();
      }
    if(is_string($moduleFilter))
      {
      $moduleFilter = array($moduleFilter);
      }
    $return = array();
    foreach($this->notifications[$name] as $key => $notification)
      {
      $module = $this->modules[$notification['module']];
      if(empty($moduleFilter) || in_array($module, $moduleFilter))
        {
        $tmp = call_user_func(array($module, $notification['call']), $params);
        if($tmp != null)
          {
          $return[$notification['module']] = $tmp;
          }
        }
      }
    return $return;
    }//end notify

  /**
   * Get Logger
   * @return Zend_Log
   */
  public function getLogger()
    {
    return Zend_Registry::get('logger');
    }

  } // end class
