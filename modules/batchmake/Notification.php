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

/** notification manager*/
class Batchmake_Notification extends MIDAS_Notification
  {
  public $_models=array('User');
  

  /** init notification process*/
  public function init()
    {
    $this->addCallBack('CALLBACK_CORE_GET_DASHBOARD', 'getDasboard');
    $this->addTask('TASK_BATCHMAKE_TEST', 'testMethod', 'test');
    $this->addEvent('EVENT_CORE_CREATE_THUMBNAIL', 'TASK_BATCHMAKE_TEST');
    }//end init


  /** generate Dasboard information */
  public function getDasboard()
    {    
    $config = Zend_Registry::get('configsModules');

    $return = array();
    $return['notice'] = 'This notification needs to be improved';
   
    return $return;
    } 
  } //end class
?>
