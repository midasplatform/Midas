<?php
/** notification manager*/
class Helloworld_Notification extends MIDAS_Notification
  {
  public $_models=array('User');

  /** init notification process*/
  public function init()
    {
    $this->addCallBack('CALLBACK_CORE_TEST', 'testMethod');
    $this->addTask('TASK_HELLOWORLD_TEST', 'testMethod', 'test');
    $this->addEvent('EVENT_CORE_CREATE_THUMBNAIL', 'TASK_HELLOWORLD_TEST');
    }//end init

  /** get Config Tabs */
  public function testMethod()
    {
    return;
    }
  } //end class
?>