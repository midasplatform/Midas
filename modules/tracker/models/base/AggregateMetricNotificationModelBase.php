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

/** AggregateMetricNotification base model class for the tracker module. */
abstract class Tracker_AggregateMetricNotificationModelBase extends Tracker_AppModel
{
    /** Constructor. */
    public function __construct()
    {
        parent::__construct();

        $this->_name = 'tracker_aggregate_metric_notification';
        $this->_daoName = 'AggregateMetricNotificationDao';
        $this->_key = 'aggregate_metric_notification_id';
        $this->_mainData = array(
            'aggregate_metric_notification_id' => array('type' => MIDAS_DATA),
            'aggregate_metric_spec_id' => array('type' => MIDAS_DATA),
            'branch' => array('type' => MIDAS_DATA),
            'value' => array('type' => MIDAS_DATA),
            'comparison' => array('type' => MIDAS_DATA),
            'aggregate_metric_spec' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'AggregateMetricSpec',
                'module' => $this->moduleName,
                'parent_column' => 'aggregate_metric_spec_id',
                'child_column' => 'aggregate_metric_spec_id',
            ),
        );

        $this->initialize();
    }

    /**
     * Create a user notification tied to the aggregate metric notification.
     *
     * @param Tracker_AggregateMetricNotificationDao $aggregateMetricNotificationDao aggregateMetricNotification DAO
     * @param UserDao $userDao user DAO
     * @return bool true if the notification could be created, false otherwise
     */
    abstract public function createUserNotification($aggregateMetricNotificationDao, $userDao);

    /**
     * Delete a user notification tied to the aggregate metric notification.
     *
     * @param Tracker_AggregateMetricNotificationDao $aggregateMetricNotificationDao aggregateMetricNotification DAO
     * @param UserDao $userDao user DAO
     * @return bool true if the user and aggregate metric notification are valid and a
     * notification does not exist for this user and aggregate metric notification upon
     * returning, false otherwise
     */
    abstract public function deleteUserNotification($aggregateMetricNotificationDao, $userDao);

    /**
     * Return a list of User Daos for all users with notifications on this aggregate metric notification.
     *
     * @param Tracker_AggregateMetricNotificationDao $aggregateMetricNotificationDao aggregateMetricNotification DAO
     * @return false|array of UserDao for all users with notification on the passed in $aggregateMetricNotificationDao,
     * or false if the passed in notification is invalid
     */
    abstract public function getAllNotifiedUsers($aggregateMetricNotificationDao);

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
     * or false if the inputs are invalid
     */
    abstract public function scheduleNotificationJobs($aggregateMetricDao);
}
