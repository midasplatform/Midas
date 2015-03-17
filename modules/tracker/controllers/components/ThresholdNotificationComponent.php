<?php
/*=========================================================================
 Midas Server
 Copyright Kitware SAS, 26 rue Louis Guérin, 69100 Villeurbanne, France.
 All rights reserved.
 For more information visit http://www.kitware.com/.

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

/**
 * Threshold notification component for the tracker module.
 *
 * @package Modules\Tracker\Component
 */
class Tracker_ThresholdNotificationComponent extends AppComponent
{
    /**
     * Add scheduled tasks for notifying users that a threshold was crossed.
     *
     * @param Tracker_ScalarDao $scalarDao scalar DAO
     * @param array $thresholdNotificationDaos threshold notification DAOs
     */
    public function scheduleNotifications($scalarDao, $thresholdNotificationDaos)
    {
        /** @var Scheduler_JobModel $jobModel */
        $jobModel = MidasLoader::loadModel('Job', 'scheduler');

        /** @var Tracker_ThresholdNotificationDao $thresholdNotificationDao */
        foreach ($thresholdNotificationDaos as $thresholdNotificationDao) {
            /** @var Scheduler_JobDao $jobDao */
            $jobDao = MidasLoader::newDao('JobDao', 'scheduler');
            $jobDao->setTask('TASK_TRACKER_SEND_THRESHOLD_NOTIFICATION');
            $jobDao->setPriority(1);
            $jobDao->setRunOnlyOnce(1);
            $jobDao->setFireTime(date('Y-m-d H:i:s'));
            $jobDao->setTimeInterval(0);
            $jobDao->setStatus(SCHEDULER_JOB_STATUS_TORUN);
            $jobDao->setCreatorId($thresholdNotificationDao->getRecipientId());
            $jobDao->setParams(JsonComponent::encode(array(
                'notification' => $thresholdNotificationDao,
                'scalar' => $scalarDao,
            )));
            $jobModel->save($jobDao);
        }
    }
}
