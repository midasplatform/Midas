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
include_once BASE_PATH . '/modules/batchmake/constant/module.php';
/** TaskModel Base class */
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
      'user_id' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'User', 'parent_column' => 'user_id', 'child_column' => 'user_id'),
      'work_dir' => array('type' => MIDAS_DATA));
    $this->initialize(); // required
    }


  /** Create a task
   * @return TaskDao */
  function createTask($userDao, $tmpWorkDirRoot)
    {
    if(!$userDao instanceof UserDao)
      {
      throw new Zend_Exception("Error parameters.");
      }
    $this->loadDaoClass('TaskDao', 'batchmake');
    $task = new Batchmake_TaskDao();
    $task->setUserId($userDao->getKey());
    $this->save($task);
    $userId = $task->getUserId();
    $taskId = $task->getKey();
    $subdirs = array(MIDAS_BATCHMAKE_SSP_DIR, $userId, $taskId);
    // create a workDir based on the task and user
    $workDir = KWUtils::createSubDirectories($tmpWorkDirRoot . "/", $subdirs);
    $task->setWorkDir($workDir);
    $this->save($task);
    return $task;
    } // end createTask()







}  // end class Batchmake_TaskModelBase