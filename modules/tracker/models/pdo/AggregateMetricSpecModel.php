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
    public function createUserNotification($aggregateMetricSpecDao, $userDao) {
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
    public function deleteUserNotification($aggregateMetricSpecDao, $userDao) {
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
    public function getAllNotifiedUsers($aggregateMetricSpecDao) {
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
     * Delete the given aggregate metric spec, any metrics calculated based on that spec,
     * and any associated notifications.
     *
     * @param Tracker_AggregateMetricSpecDao $aggregateMetricSpecDao aggregateMetricSpec DAO
     */
    public function delete($aggregateMetricSpecDao) {
        if (is_null($aggregateMetricSpecDao) || $aggregateMetricSpecDao === false) {
            return;
        }
        $this->database->getDB()->delete('tracker_user2aggregate_metric_spec', 'aggregate_metric_spec_id = '.$aggregateMetricSpecDao->getAggregateMetricSpecId());
        $this->database->getDB()->delete('tracker_aggregate_metric', 'aggregate_metric_spec_id = '.$aggregateMetricSpecDao->getAggregateMetricSpecId());
        $this->database->getDB()->delete('tracker_aggregate_metric_spec', 'aggregate_metric_spec_id = '.$aggregateMetricSpecDao->getAggregateMetricSpecId());

        parent::delete($aggregateMetricSpecDao);
    }
}
