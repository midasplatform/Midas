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

    $taskModel = MidasLoader::loadModel('Task', 'batchmake');

    $user1Dao = $usersFile[0];

    $tmpWorkDirRoot = $this->getTempDirectory() . '/' . 'test';
    KWUtils::mkDir($tmpWorkDirRoot);

    $task1Dao = $taskModel->createTask($user1Dao, $tmpWorkDirRoot);

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

    $task2Dao = $taskModel->createTask($user2Dao, $tmpWorkDirRoot);
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