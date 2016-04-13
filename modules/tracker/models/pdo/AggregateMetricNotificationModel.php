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

require_once BASE_PATH.'/modules/tracker/models/base/AggregateMetricNotificationModelBase.php';

/** AggregateMetricNotification model for the tracker module. */
class Tracker_AggregateMetricNotificationModel extends Tracker_AggregateMetricNotificationModelBase
{
    /**
     * Create a user notification tied to the aggregate metric notification.
     *
     * @param Tracker_AggregateMetricNotificationDao $aggregateMetricNotificationDao aggregateMetricNotification DAO
     * @param UserDao $userDao user DAO
     * @return bool true if the notification could be created, false otherwise
     */
    public function createUserNotification($aggregateMetricNotificationDao, $userDao)
    {
        if (is_null($aggregateMetricNotificationDao) || $aggregateMetricNotificationDao === false) {
            return false;
        }
        if (is_null($userDao) || $userDao === false) {
            return false;
        }
        // Don't insert if it exists already.
        $sql = $this->database->select()->setIntegrityCheck(false)
            ->from('tracker_user2aggregate_metric_notification')
            ->where('aggregate_metric_notification_id = ?', $aggregateMetricNotificationDao->getAggregateMetricNotificationId())
            ->where('user_id = ?', $userDao->getUserId());
        /** @var Zend_Db_Table_Row_Abstract $row */
        $row = $this->database->fetchRow($sql);
        if (!is_null($row)) {
            return true;
        } else {
            $data = array(
                'aggregate_metric_notification_id' => $aggregateMetricNotificationDao->getAggregateMetricNotificationId(),
                'user_id' => $userDao->getUserId(),
            );
            $this->database->getdb()->insert('tracker_user2aggregate_metric_notification', $data);

            return true;
        }
    }

    /**
     * Delete a user notification tied to the aggregate metric notification.
     *
     * @param Tracker_AggregateMetricNotificationDao $aggregateMetricNotificationDao aggregateMetricNotification DAO
     * @param UserDao $userDao user DAO
     * @return bool true if the user and aggregate metric notification are valid and a
     * notification does not exist for this user and aggregate metric notification upon
     * returning, false otherwise
     */
    public function deleteUserNotification($aggregateMetricNotificationDao, $userDao)
    {
        if (is_null($aggregateMetricNotificationDao) || $aggregateMetricNotificationDao === false) {
            return false;
        }
        if (is_null($userDao) || $userDao === false) {
            return false;
        }
        $this->database->getDB()->delete('tracker_user2aggregate_metric_notification', array(
            'aggregate_metric_notification_id = ?' => $aggregateMetricNotificationDao->getAggregateMetricNotificationId(),
            'user_id = ?' => $userDao->getUserId(),
        ));

        return true;
    }

    /**
     * Return a list of User Daos for all users with notifications on this aggregate metric notification.
     *
     * @param Tracker_AggregateMetricNotificationDao $aggregateMetricNotificationDao aggregateMetricNotification DAO
     * @return false|array of UserDao for all users with notification on the passed in $aggregateMetricNotificationDao,
     * or false if the passed in notification is invalid
     */
    public function getAllNotifiedUsers($aggregateMetricNotificationDao)
    {
        if (is_null($aggregateMetricNotificationDao) || $aggregateMetricNotificationDao === false) {
            return false;
        }
        $sql = $this->database->select()->setIntegrityCheck(false)
                    ->from('tracker_user2aggregate_metric_notification', array('user_id'))
                    ->where('aggregate_metric_notification_id = ?', $aggregateMetricNotificationDao->getAggregateMetricNotificationId());
        $rows = $this->database->fetchAll($sql);

        $userDaos = array();
        /** @var userModel $userModel */
        $userModel = MidasLoader::loadModel('User');
        /** @var Zend_Db_Table_Row_Abstract $row */
        foreach ($rows as $row) {
            $userDaos[] = $userModel->load($row['user_id']);
        }

        return $userDaos;
    }

    /**
     * Return a list of Jobs scheduled to notify users, if the passed aggregate metric
     * is beyond the threshold of any notifications tied to the aggregate metric spec
     * that generated the aggregate metric.
     *
     * @param Tracker_AggregateMetricNotificationDao $aggregateMetricNotification
     * @return false|array of Scheduler_JobDao for all users with a notification
     * created, which will only be populated if the aggregate metric is beyond
     * the threshold defined on any aggregate metric notification tied to the
     * aggregate metric spec that generated the aggregate metric and there exist
     * users to be notified on the aggregate metric notification,
     * or false if the inputs are invalid.
     */
    public function scheduleNotificationJobs($aggregateMetricDao)
    {
        if (is_null($aggregateMetricDao) || $aggregateMetricDao === false) {
            return false;
        }

        /** @var string $branch */
        $branch = $aggregateMetricDao->getSubmission()->getBranch();
        /** @var Tracker_AggregateMetricSpecDao $aggregateMetricSpecDao */
        $aggregateMetricSpecDao = $aggregateMetricDao->getAggregateMetricSpec();
        $jobs = array();

        // Get all notifications tied to that spec and branch.
        $sql = $this->database->select()->setIntegrityCheck(false)
            ->from('tracker_aggregate_metric_notification')
            ->where('aggregate_metric_spec_id = ?', $aggregateMetricSpecDao->getAggregateMetricSpecId())
            ->where('branch = ?', $branch);
        $rows = $this->database->fetchAll($sql);
        /** @var Zend_Db_Table_Row_Abstract $row */
        foreach ($rows as $row) {
            $aggregateMetricNotificationDao = $this->initDao('AggregateMetricNotification', $row, $this->moduleName);
            $value = floatval($aggregateMetricDao->getValue());
            $thresholdValue = floatval($aggregateMetricNotificationDao->getValue());
            /** @var bool $aboveThreshold */
            $aboveThreshold = false;
            switch ($aggregateMetricNotificationDao->getComparison()) {
                case '>':
                    $aboveThreshold = $value > $thresholdValue;
                    break;
                case '<':
                    $aboveThreshold = $value < $thresholdValue;
                    break;
                case '>=':
                    $aboveThreshold = $value >= $thresholdValue;
                    break;
                case '<=':
                    $aboveThreshold = $value <= $thresholdValue;
                    break;
                case '==':
                    $aboveThreshold = $value === $thresholdValue;
                    break;
                case '!=':
                    $aboveThreshold = $value !== $thresholdValue;
                    break;
                default:
                    $aboveThreshold = false;
            }

            if ($aboveThreshold) {
                $notifiedUsers = $this->getAllNotifiedUsers($aggregateMetricNotificationDao);
                if ($notifiedUsers && count($notifiedUsers) > 0) {
                    /** @var Scheduler_JobModel $jobModel */
                    $jobModel = MidasLoader::loadModel('Job', 'scheduler');
                    /** @var userDao $userDao */
                    foreach ($notifiedUsers as $userDao) {
                        /** @var Scheduler_JobDao $jobDao */
                        $jobDao = MidasLoader::newDao('JobDao', 'scheduler');
                        $jobDao->setTask('TASK_TRACKER_SEND_AGGREGATE_METRIC_NOTIFICATION');
                        $jobDao->setPriority(MIDAS_EVENT_PRIORITY_HIGH);
                        $jobDao->setRunOnlyOnce(1);
                        $jobDao->setFireTime(date('Y-m-d H:i:s'));
                        $jobDao->setTimeInterval(0);
                        $jobDao->setStatus(SCHEDULER_JOB_STATUS_TORUN);
                        $jobDao->setCreatorId($userDao->getUserId());
                        $jobDao->setParams(JsonComponent::encode(array(
                            'aggregate_metric_notification_id' => $aggregateMetricNotificationDao->getAggregateMetricNotificationId(),
                            'aggregate_metric_id' => $aggregateMetricDao->getAggregateMetricId(),
                            'recipient_id' => $userDao->getUserId(),
                        )));
                        $jobModel->save($jobDao);
                        $jobs[] = $jobDao;
                    }
                }
            }
        }

        return $jobs;
    }

    /**
     * Delete the given aggregate metric notification, and any associated user
     * notifications.
     *
     * @param Tracker_AggregateMetricNotificationDao $aggregateMetricNotificationDao aggregateMetricNotification DAO
     */
    public function delete($aggregateMetricNotificationDao)
    {
        if (is_null($aggregateMetricNotificationDao) || $aggregateMetricNotificationDao === false) {
            return;
        }
        $this->database->getDB()->delete('tracker_user2aggregate_metric_notification', 'aggregate_metric_notification_id = '.$aggregateMetricNotificationDao->getAggregateMetricNotificationId());
        parent::delete($aggregateMetricNotificationDao);
    }
}
