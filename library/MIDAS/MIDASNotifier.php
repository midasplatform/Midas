<?php

/** Notify modules using this class*/
class MIDAS_Notifier
  {
  
  private $modules = array();
  
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
      $this->modules[$module] = new $name();
      }
    }//end contruct() 
    
  /** notify enabled modules*/
  public function notify($type, $params)
    {
    $return = array();
    foreach($this->modules as $key => $module)
      {
      $return[$key] = call_user_func(array($module, 'init'), $type, $params);
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