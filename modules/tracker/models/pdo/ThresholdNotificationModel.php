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

require_once BASE_PATH.'/modules/tracker/models/base/ThresholdNotificationModelBase.php';

/**
 * Threshold notification model for the tracker module.
 *
 * @package Modules\Tracker\Model
 */
class Tracker_ThresholdNotificationModel extends Tracker_ThresholdNotificationModelBase
{
    /**
     * Return the threshold notifications whose conditions are met by the given scalar.
     *
     * @param Tracker_ScalarDao $scalar scalar DAO
     * @return array threshold notification DAOs
     */
    public function getNotifications($scalar)
    {
        $sql = $this->database->select()->setIntegrityCheck(false)->where(
            'trend_id = ?',
            $scalar->getTrend()->getKey()
        );
        $rows = $this->database->fetchAll($sql);
        $thresholds = array();

        /** @var Zend_Db_Table_Row_Abstract $row */
        foreach ($rows as $row) {
            /** @var Tracker_ThresholdNotificationDao $threshold */
            $threshold = $this->initDao('ThresholdNotification', $row, $this->moduleName);
            if ($this->testThreshold($scalar->getValue(), $threshold)) {
                $thresholds[] = $threshold;
            }
        }

        return $thresholds;
    }

    /**
     * Return the threshold notification for the given user and trend.
     *
     * @param UserDao $user user DAO
     * @param Tracker_TrendDao $trend trend DAO
     * @return false|Tracker_ThresholdNotificationDao threshold notification DAO or false if none exists
     */
    public function getUserSetting($user, $trend)
    {
        $sql = $this->database->select()->setIntegrityCheck(false)->where('trend_id = ?', $trend->getKey())->where(
            'action = ?',
            MIDAS_TRACKER_EMAIL_USER
        )->where('recipient_id = ?', $user->getKey());

        return $this->initDao('ThresholdNotification', $this->database->fetchRow($sql), $this->moduleName);
    }

    /**
     * Delete all thresholds for the given trend.
     *
     * @param Tracker_TrendDao $trend trend DAO
     */
    public function deleteByTrend($trend)
    {
        $sql = $this->database->select()->setIntegrityCheck(false)->where('trend_id = ?', $trend->getKey());
        $rows = $this->database->fetchAll($sql);

        /** @var Zend_Db_Table_Row_Abstract $row */
        foreach ($rows as $row) {
            /** @var Tracker_ThresholdNotificationDao $threshold */
            $threshold = $this->initDao('ThresholdNotification', $row, $this->moduleName);
            $this->delete($threshold);
        }
    }
}
