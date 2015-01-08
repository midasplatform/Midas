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

/** Admin component for the statistics module. */
class Statistics_AdminComponent extends AppComponent
{
    /** @var string */
    public $moduleName = 'statistics';

    /**
     * Schedule the perform geolocation job.
     *
     * @param string $apiKey IPInfoDb API key
     * @param null|UserDao $userDao user scheduling the job
     */
    public function schedulePerformGeolocationJob($apiKey, $userDao = null)
    {
        /** @var Scheduler_JobModel $jobModel */
        $jobModel = MidasLoader::loadModel('Job', 'scheduler');
        $jobDaos = $jobModel->getJobsByTask('TASK_STATISTICS_PERFORM_GEOLOCATION');
        $geolocationJobDao = false;

        foreach ($jobDaos as $jobDao) {
            if ($jobDao->getTask() === 'TASK_STATISTICS_PERFORM_GEOLOCATION') {
                $geolocationJobDao = $jobDao;
                break;
            }
        }

        if ($geolocationJobDao === false) {
            /** @var Scheduler_JobDao $geolocationJobDao */
            $geolocationJobDao = MidasLoader::newDao('JobDao', 'scheduler');
            $geolocationJobDao->setTask('TASK_STATISTICS_PERFORM_GEOLOCATION');
            $geolocationJobDao->setPriority(1);
            $geolocationJobDao->setRunOnlyOnce(0);
            $geolocationJobDao->setFireTime(date('Y-m-d', strtotime('+1 day'.date('Y-m-d H:i:s'))).' 01:00:00');
            $geolocationJobDao->setTimeInterval(3600);
            $geolocationJobDao->setStatus(SCHEDULER_JOB_STATUS_TORUN);

            if (!is_null($userDao)) {
                $geolocationJobDao->setCreatorId($userDao->getKey());
            }
        }

        $geolocationJobDao->setParams(JsonComponent::encode(array('apikey' => $apiKey)));
        $geolocationJobDao->setStatus(SCHEDULER_JOB_STATUS_TORUN);
        $jobModel->save($geolocationJobDao);
    }

    /**
     * Schedule or cancel the send report job.
     *
     * @param bool $schedule schedule the job if true, cancel the job if false
     * @param null|UserDao $userDao user scheduling the job
     *
     * @throws Zend_Exception
     */
    public function scheduleOrCancelSendReportJob($schedule, $userDao = null)
    {
        /** @var Scheduler_JobModel $jobModel */
        $jobModel = MidasLoader::loadModel('Job', 'scheduler');
        $jobDaos = $jobModel->getJobsByTask('TASK_STATISTICS_SEND_REPORT');
        $reportJobDao = false;

        foreach ($jobDaos as $jobDao) {
            if ($jobDao->getTask() === 'TASK_STATISTICS_SEND_REPORT') {
                $reportJobDao = $jobDao;
                break;
            }
        }

        if ($schedule) {
            if ($reportJobDao === false) {
                /** @var Scheduler_JobDao $reportJobDao */
                $reportJobDao = MidasLoader::newDao('JobDao', 'scheduler');
                $reportJobDao->setTask('TASK_STATISTICS_SEND_REPORT');
                $reportJobDao->setPriority(1);
                $reportJobDao->setRunOnlyOnce(0);
                $reportJobDao->setFireTime(date('Y-m-d', strtotime('+1 day'.date('Y-m-d H:i:s'))).' 01:00:00');
                $reportJobDao->setTimeInterval(86400);
                $reportJobDao->setStatus(SCHEDULER_JOB_STATUS_TORUN);
                $reportJobDao->setCreatorId($this->userSession->Dao->getKey());
                $reportJobDao->setParams(JsonComponent::encode(array()));

                if (!is_null($userDao)) {
                    $reportJobDao->setCreatorId($userDao->getKey());
                }

                $jobModel->save($reportJobDao);
            }
        } else {
            if ($reportJobDao !== false) {
                $jobModel->delete($reportJobDao);
            }
        }
    }
}
