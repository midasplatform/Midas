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

require_once BASE_PATH.'/modules/tracker/models/base/ThresholdNotificationModelBase.php';

/** Threshold notification model for the tracker module. */
class Tracker_ThresholdNotificationModel extends Tracker_ThresholdNotificationModelBase
{
    /**
     * Return the threshold notifications whose conditions are met by the given scalar.
     *
     * @param Tracker_ScalarDao $scalarDao scalar DAO
     * @return array threshold notification DAOs
     */
    public function getNotifications($scalarDao)
    {
        $sql = $this->database->select()->setIntegrityCheck(false)->where(
            'trend_id = ?',
            $scalarDao->getTrend()->getKey()
        );
        $rows = $this->database->fetchAll($sql);
        $thresholdNotificationDaos = array();

        /** @var Zend_Db_Table_Row_Abstract $row */
        foreach ($rows as $row) {
            /** @var Tracker_ThresholdNotificationDao $thresholdNotificationDao */
            $thresholdNotificationDao = $this->initDao('ThresholdNotification', $row, $this->moduleName);

            if ($this->testThreshold($scalarDao->getValue(), $thresholdNotificationDao)) {
                $thresholdNotificationDaos[] = $thresholdNotificationDao;
            }
        }

        return $thresholdNotificationDaos;
    }

    /**
     * Return the threshold notification for the given user and trend.
     *
     * @param UserDao $userDao user DAO
     * @param Tracker_TrendDao $trendDao trend DAO
     * @return false|Tracker_ThresholdNotificationDao threshold notification DAO or false if none exists
     */
    public function getUserSetting($userDao, $trendDao)
    {
        $sql = $this->database->select()->setIntegrityCheck(false)->where('trend_id = ?', $trendDao->getKey())->where(
            'action = ?',
            MIDAS_TRACKER_EMAIL_USER
        )->where('recipient_id = ?', $userDao->getKey());

        return $this->initDao('ThresholdNotification', $this->database->fetchRow($sql), $this->moduleName);
    }

    /**
     * Delete all thresholds for the given trend.
     *
     * @param Tracker_TrendDao $trendDao trend DAO
     */
    public function deleteByTrend($trendDao)
    {
        $sql = $this->database->select()->setIntegrityCheck(false)->where('trend_id = ?', $trendDao->getKey());
        $rows = $this->database->fetchAll($sql);

        /** @var Zend_Db_Table_Row_Abstract $row */
        foreach ($rows as $row) {
            /** @var Tracker_ThresholdNotificationDao $thresholdNotificationDao */
            $thresholdNotificationDao = $this->initDao('ThresholdNotification', $row, $this->moduleName);
            $this->delete($thresholdNotificationDao);
        }
    }
}
