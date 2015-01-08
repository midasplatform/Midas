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

include_once BASE_PATH.'/modules/batchmake/constant/module.php';

/** TaskModel Base class */
class Batchmake_TaskModelBase extends Batchmake_AppModel
{
    /** Constructor. */
    public function __construct()
    {
        parent::__construct();
        $this->_name = 'batchmake_task';
        $this->_key = 'batchmake_task_id';

        $this->_mainData = array(
            'batchmake_task_id' => array('type' => MIDAS_DATA),
            'user_id' => array('type' => MIDAS_DATA),
            'work_dir' => array('type' => MIDAS_DATA),
        );
        $this->initialize(); // required
    }

    /**
     * Create a task.
     *
     * @param UserDao $userDao
     * @param string $tmpWorkDirRoot
     * @return Batchmake_TaskDao
     * @throws Zend_Exception
     */
    public function createTask($userDao, $tmpWorkDirRoot)
    {
        if (!$userDao instanceof UserDao) {
            throw new Zend_Exception("Error parameters.");
        }

        /** @var Batchmake_TaskDao $task */
        $task = MidasLoader::newDao('TaskDao', 'batchmake');
        $task->setUserId($userDao->getKey());
        $this->save($task);
        $userId = $task->getUserId();
        $taskId = $task->getKey();
        $subdirs = array(MIDAS_BATCHMAKE_SSP_DIR, $userId, $taskId);
        // create a workDir based on the task and user
        $workDir = KWUtils::createSubDirectories($tmpWorkDirRoot."/", $subdirs);
        $task->setWorkDir($workDir);
        $this->save($task);

        return $task;
    }
}
