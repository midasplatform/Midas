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

/** Admin component for the cleanup module. */
class Cleanup_AdminComponent extends AppComponent
{
    /** @var string */
    public $moduleName = 'cleanup';

    /**
     * Schedule the perform cleanup job.
     *
     * @param int $days days to keep partial files
     * @param string $tempDirectory temporary directory
     * @param null|UserDao $userDao user scheduling the job
     */
    public function schedulePerformCleanupJob($days, $tempDirectory, $userDao = null)
    {
        /**  @var Scheduler_JobModel $jobModel */
        $jobModel = MidasLoader::loadModel('Job', 'scheduler');
        $jobDaos = $jobModel->getJobsByTask('TASK_CLEANUP_PERFORM_CLEANUP');
        $cleanupJobDao = false;

        foreach ($jobDaos as $jobDao) {
            if ($jobDao->getTask() === 'TASK_CLEANUP_PERFORM_CLEANUP') {
                $cleanupJobDao = $jobDao;
                break;
            }
        }

        if ($cleanupJobDao === false) {
            /** @var Scheduler_JobDao $cleanupJobDao */
            $cleanupJobDao = MidasLoader::newDao('JobDao', 'scheduler');
            $cleanupJobDao->setTask('TASK_CLEANUP_PERFORM_CLEANUP');
            $cleanupJobDao->setPriority(1);
            $cleanupJobDao->setRunOnlyOnce(0);
            $cleanupJobDao->setFireTime(date('Y-m-d', strtotime('+1 day'.date('Y-m-d H:i:s'))).' 01:00:00');
            $cleanupJobDao->setTimeInterval(86400);
            $cleanupJobDao->setStatus(SCHEDULER_JOB_STATUS_TORUN);

            if (!is_null($userDao)) {
                $cleanupJobDao->setCreatorId($userDao->getKey());
            }
        }

        $cleanupJobDao->setParams(JsonComponent::encode(array('days' => $days, 'tempDirectory' => $tempDirectory)));

        $jobModel->save($cleanupJobDao);
    }
}
