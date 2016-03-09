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

require_once BASE_PATH.'/modules/tracker/models/base/AggregateMetricSpecModelBase.php';

/** AggregateMetricSpec model for the tracker module. */
class Tracker_AggregateMetricSpecModel extends Tracker_AggregateMetricSpecModelBase
{
    /**
     * Create a user notification tied to the aggregate metric spec.
     *
     * @param Tracker_AggregateMetricSpecDao $aggregateMetricSpecDao aggregateMetricSpec DAO
     * @param UserDao $userDao user DAO
     * @return bool true if the notification could be created, false otherwise
     */
    public function createUserNotification($aggregateMetricSpecDao, $userDao)
    {
        if (is_null($aggregateMetricSpecDao) || $aggregateMetricSpecDao === false) {
            return false;
        }
        if (is_null($userDao) || $userDao === false) {
            return false;
        }
        // Don't insert if it exists already.
        $sql = $this->database->select()->setIntegrityCheck(false)
            ->from('tracker_user2aggregate_metric_spec')
            ->where('aggregate_metric_spec_id = ?', $aggregateMetricSpecDao->getAggregateMetricSpecId())
            ->where('user_id = ?', $userDao->getUserId());
        /** @var Zend_Db_Table_Row_Abstract $row */
        $row = $this->database->fetchRow($sql);
        if (!is_null($row)) {
            return true;
        } else {
            $data = array(
                'aggregate_metric_spec_id' => $aggregateMetricSpecDao->getAggregateMetricSpecId(),
                'user_id' => $userDao->getUserId(),
            );
            $this->database->getdb()->insert('tracker_user2aggregate_metric_spec', $data);

            return true;
        }
    }

    /**
     * Delete a user notification tied to the aggregate metric spec.
     *
     * @param Tracker_AggregateMetricSpecDao $aggregateMetricSpecDao aggregateMetricSpec DAO
     * @param UserDao $userDao user DAO
     * @return bool true if the user and aggregate metric spec are valid and a
     * notification does not exist for this user and aggregate metric spec upon
     * returning, false otherwise
     */
    public function deleteUserNotification($aggregateMetricSpecDao, $userDao)
    {
        if (is_null($aggregateMetricSpecDao) || $aggregateMetricSpecDao === false) {
            return false;
        }
        if (is_null($userDao) || $userDao === false) {
            return false;
        }
        $this->database->getDB()->delete('tracker_user2aggregate_metric_spec', array(
            'aggregate_metric_spec_id = ?' => $aggregateMetricSpecDao->getAggregateMetricSpecId(),
            'user_id = ?' => $userDao->getUserId(),
        ));

        return true;
    }

    /**
     * Return a list of User Daos for all users with notifications on this aggregate metric spec.
     *
     * @param Tracker_AggregateMetricSpecDao $aggregateMetricSpecDao aggregateMetricSpec DAO
     * @return false|array of UserDao for all users with notification on the passed in $aggregateMetricSpecDao,
     * or false if the passed in spec is invalid
     */
    public function getAllNotifiedUsers($aggregateMetricSpecDao)
    {
        if (is_null($aggregateMetricSpecDao) || $aggregateMetricSpecDao === false) {
            return false;
        }
        $sql = $this->database->select()->setIntegrityCheck(false)
                    ->from('tracker_user2aggregate_metric_spec', array('user_id'))
                    ->where('aggregate_metric_spec_id = ?', $aggregateMetricSpecDao->getAggregateMetricSpecId());
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
     * Return a list of Jobs scheduled to notify users that the passed aggregate metric is above
     * the threshold defined in the passed aggregate metric spec.
     *
     * @param Tracker_AggregateMetricSpecDao $aggregateMetricSpecDao aggregateMetricSpec DAO
     * @param Tracker_AggregateMetricDao $aggregateMetricDao aggregateMetric DAO
     * @return false|array of Scheduler_JobDao for all users with a notification created, which will only
     * be populated if the aggregate metric is above the threshold defined on the aggregate metric spec and
     * there exist users to be notified on the aggregate metric spec, or false if the inputs are invalid.
     */
    public function scheduleNotificationJobs($aggregateMetricSpecDao, $aggregateMetricDao)
    {
        if (is_null($aggregateMetricSpecDao) || $aggregateMetricSpecDao === false) {
            return false;
        }
        if (is_null($aggregateMetricDao) || $aggregateMetricDao === false) {
            return false;
        }

        // if the value exists and the threshold exists, test it
        $value = $aggregateMetricDao->getValue();
        $thresholdValue = $aggregateMetricSpecDao->getValue();
        /** @var bool $aboveThreshold */
        $aboveThreshold = false;
        switch ($aggregateMetricSpecDao->getComparison()) {
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

        $jobs = array();
        if ($aboveThreshold) {
            $notifiedUsers = $this->getAllNotifiedUsers($aggregateMetricSpecDao);
            if ($notifiedUsers && count($notifiedUsers) > 0) {
                /** @var Scheduler_JobModel $jobModel */
                $jobModel = MidasLoader::loadModel('Job', 'scheduler');
                /** @var userDao $userDao */
                foreach ($notifiedUsers as $userDao) {
                    /** @var Scheduler_JobDao $jobDao */
                    $jobDao = MidasLoader::newDao('JobDao', 'scheduler');
                    $jobDao->setTask('TASK_TRACKER_SEND_AGGREGATE_METRIC_NOTIFICATION');
                    $jobDao->setPriority(1);
                    $jobDao->setRunOnlyOnce(1);
                    $jobDao->setFireTime(date('Y-m-d H:i:s'));
                    $jobDao->setTimeInterval(0);
                    $jobDao->setStatus(SCHEDULER_JOB_STATUS_TORUN);
                    $jobDao->setCreatorId($userDao->getUserId());
                    $jobDao->setParams(JsonComponent::encode(array(
                        'aggregate_metric_spec_id' => $aggregateMetricSpecDao->getAggregateMetricSpecId(),
                        'aggregate_metric_id' => $aggregateMetricDao->getAggregateMetricId(),
                        'recipient_id' => $userDao->getUserId(),
                    )));
                    $jobModel->save($jobDao);
                    $jobs[] = $jobDao;
                }
            }
        }

        return $jobs;
    }

    /**
     * Delete the given aggregate metric spec, any metrics calculated based on that spec,
     * and any associated notifications.
     *
     * @param Tracker_AggregateMetricSpecDao $aggregateMetricSpecDao aggregateMetricSpec DAO
     */
    public function delete($aggregateMetricSpecDao)
    {
        if (is_null($aggregateMetricSpecDao) || $aggregateMetricSpecDao === false) {
            return;
        }
        $this->database->getDB()->delete('tracker_user2aggregate_metric_spec', 'aggregate_metric_spec_id = '.$aggregateMetricSpecDao->getAggregateMetricSpecId());
        $this->database->getDB()->delete('tracker_aggregate_metric', 'aggregate_metric_spec_id = '.$aggregateMetricSpecDao->getAggregateMetricSpecId());
        $this->database->getDB()->delete('tracker_aggregate_metric_spec', 'aggregate_metric_spec_id = '.$aggregateMetricSpecDao->getAggregateMetricSpecId());

        parent::delete($aggregateMetricSpecDao);
    }
}
