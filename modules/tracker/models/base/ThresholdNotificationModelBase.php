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

/** Threshold notification base model class for the tracker module. */
abstract class Tracker_ThresholdNotificationModelBase extends Tracker_AppModel
{
    /** Constructor. */
    public function __construct()
    {
        parent::__construct();

        $this->_name = 'tracker_threshold_notification';
        $this->_daoName = 'ThresholdNotificationDao';
        $this->_key = 'threshold_id';
        $this->_mainData = array(
            'threshold_id' => array('type' => MIDAS_DATA),
            'trend_id' => array('type' => MIDAS_DATA),
            'value' => array('type' => MIDAS_DATA),
            'comparison' => array('type' => MIDAS_DATA),
            'action' => array('type' => MIDAS_DATA),
            'recipient_id' => array('type' => MIDAS_DATA),
            'trend' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'Trend',
                'module' => $this->moduleName,
                'parent_column' => 'trend_id',
                'child_column' => 'trend_id',
            ),
            'recipient' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'User',
                'parent_column' => 'recipient_id',
                'child_column' => 'user_id',
            ),
        );

        $this->initialize();
    }

    /**
     * Return the threshold notifications whose conditions are met by the given scalar.
     *
     * @param Tracker_ScalarDao $scalarDao scalar DAO
     * @return array threshold notification DAOs
     */
    abstract public function getNotifications($scalarDao);

    /**
     * Return the threshold notification for the given user and trend.
     *
     * @param UserDao $userDao user DAO
     * @param Tracker_TrendDao $trendDao trend DAO
     * @return false|Tracker_ThresholdNotificationDao threshold notification DAO or false if none exists
     */
    abstract public function getUserSetting($userDao, $trendDao);

    /**
     * Delete all thresholds for the given trend.
     *
     * @param Tracker_TrendDao $trendDao trend DAO
     */
    abstract public function deleteByTrend($trendDao);

    /**
     * Check whether the given scalar value meets the threshold notification condition.
     *
     * @param float $value scalar value
     * @param Tracker_ThresholdNotificationDao $thresholdNotificationDao threshold notification DAO
     * @return bool true if the threshold notification condition was met
     */
    public function testThreshold($value, $thresholdNotificationDao)
    {
        $thresholdValue = $thresholdNotificationDao->getValue();

        switch ($thresholdNotificationDao->getComparison()) {
            case '>':
                return $value > $thresholdValue;
            case '<':
                return $value < $thresholdValue;
            case '>=':
                return $value >= $thresholdValue;
            case '<=':
                return $value <= $thresholdValue;
            case '==':
                return $value === $thresholdValue;
            case '!=':
                return $value !== $thresholdValue;
            default:
                return false;
        }
    }
}
