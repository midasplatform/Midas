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
/** TaskModelTest*/
class TaskModelTest extends DatabaseTestCase
  {

  /** init test*/
  public function setUp()
    {
    $this->enabledModules = array('batchmake');
    parent::setUp();
    $this->setupDatabase(array('default'), 'batchmake'); // module dataset
    }


  /** Test that TaskModel::createTask($userDao) works */
  public function testCreateTask()
    {
    $usersFile = $this->loadData('User', 'default', '', 'batchmake');

    $modelLoad = new MIDAS_ModelLoader();
    $taskModel = $modelLoad->loadModel('Task', 'batchmake');

    $user1Dao = $usersFile[0];

    $task1Dao = $taskModel->createTask($user1Dao);

    $this->assertNotEmpty($task1Dao);
    $this->assertTrue($task1Dao instanceof Batchmake_TaskDao);
    $userId1 = $task1Dao->getUserId();
    $this->assertTrue(is_numeric($userId1));
    $this->assertFalse(is_object($userId1));
    $this->assertEquals($userId1, $user1Dao->getUserId());
    $taskId1 = $task1Dao->getKey();
    $this->assertTrue(is_numeric($taskId1));
    $this->assertFalse(is_object($taskId1));

    // now try a different user
    $user2Dao = $usersFile[1];

    $task2Dao = $taskModel->createTask($user2Dao);
    $this->assertNotEmpty($task2Dao);
    $this->assertTrue($task2Dao instanceof Batchmake_TaskDao);
    $userId2 = $task2Dao->getUserId();
    $this->assertTrue(is_numeric($userId2));
    $this->assertFalse(is_object($userId2));
    $this->assertEquals($userId2, $user2Dao->getUserId());
    $taskId2 = $task2Dao->getKey();
    $this->assertTrue(is_numeric($taskId2));
    $this->assertFalse(is_object($taskId2));

    // make sure each of the tasks got a different id
    $this->assertNotEquals($taskId1, $taskId2);


    // now try to retrieve it by key
    $task3Dao = $taskModel->load($taskId1);
    $this->assertTrue($taskModel->compareDao($task1Dao, $task3Dao));

    $task4Dao = $taskModel->load($taskId2);
    $this->assertTrue($taskModel->compareDao($task2Dao, $task4Dao));
    }


  }