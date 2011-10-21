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
/** Batchmake_TaskModelBase */
class Batchmake_TaskModelBase extends Batchmake_AppModel {

  /**
   * constructor
   */
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'batchmake_task';
    $this->_key = 'batchmake_task_id';
    $this->_mainData = array(
      'batchmake_task_id' => array('type' => MIDAS_DATA),
      'user_id' => array('type' => MIDAS_DATA, )
       );
    $this->initialize(); // required
    }


  /** Create a task
   * @return TaskDao */
  function createTask($userDao)
    {
    if(!$userDao instanceof UserDao)
      {
      throw new Zend_Exception("Error parameters.");
      }
    $this->loadDaoClass('TaskDao', 'batchmake');
    $task = new Batchmake_TaskDao();
    $task->setUserId($userDao->getKey());
    $this->save($task);

    return $task;
    } // end createTask()







}  // end class Batchmake_TaskModelBase