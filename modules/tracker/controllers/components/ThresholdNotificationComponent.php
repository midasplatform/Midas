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
     * @param Tracker_ScalarDao $scalar scalar DAO
     * @param array $notifications threshold notification DAOs
     */
    public function scheduleNotifications($scalar, $notifications)
    {
        /** @var Scheduler_JobModel $jobModel */
        $jobModel = MidasLoader::loadModel('Job', 'scheduler');

        /** @var Tracker_ThresholdNotificationDao $notification */
        foreach ($notifications as $notification) {
            /** @var Scheduler_JobDao $job */
            $job = MidasLoader::newDao('JobDao', 'scheduler');
            $job->setTask('TASK_TRACKER_SEND_THRESHOLD_NOTIFICATION');
            $job->setPriority(1);
            $job->setRunOnlyOnce(1);
            $job->setFireTime(date('Y-m-d H:i:s'));
            $job->setTimeInterval(0);
            $job->setStatus(SCHEDULER_JOB_STATUS_TORUN);
            $job->setCreatorId($notification->getRecipientId());
            $job->setParams(JsonComponent::encode(array('notification' => $notification, 'scalar' => $scalar)));
            $jobModel->save($job);
        }
    }
}
