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

/** Notify modules using this class*/
class MIDAS_Notifier
  {  
  public $modules = array();
  public $tasks = array();
  private $tasksByModule = array();
  public $notifications = array();
  
  /** init the notifier*/
  public function __construct()
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
    
  /** notify enabled modules*/
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
    $return = array();
    foreach($this->notifications[$name] as $key => $notification)
      {
      $module = $this->modules[$notification['module']];
      if(empty($moduleFilter) || in_array($module, $moduleFilter))
        {
        $this->_setTask($notification['call'], $params, $notification['priority']);
        }
      }
    return $return;
    }//end notify
    
  /** schedule or execute a task*/
  private function _setTask($name, $params, $priority)
    {
    $modules = Zend_Registry::get('modulesEnable');
    if(!isset($this->tasks[$name]))
      {
      return;
      }
    if(isset($this->modules['scheduler']))
      {
      $params = array('task' => $name, 'priority' => $priority, 'params' => $params, 'run_only_once' => true, 'fire_time' => date('c'));
      call_user_func(array($this->modules['scheduler'], $this->tasks['TASK_SCHEDULER_SCHEDULE_TASK']['method']), $params);
      }
    else
      {
      call_user_func(array($this->modules[$this->tasks[$name]['module']], $this->tasks[$name]['method']), $params);
      }
    }
    
  /** notify enabled modules*/
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