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
 * Threshold Notification Model Base
 */
abstract class Tracker_ThresholdNotificationModelBase extends Tracker_AppModel
{
    /** constructor */
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
        );
        $this->initialize();
    }

    /** Get notifications */
    abstract public function getNotifications($scalar);

    /** Get user setting */
    abstract public function getUserSetting($user, $trend);

    /** Delete by trend */
    abstract public function deleteByTrend($trend);

    /**
     * Check whether the given scalar value meets the threshold condition.
     * Returns true if the action should be taken, i.e. the threshold was crossed.
     */
    public function testThreshold($value, $threshold)
    {
        switch ($threshold->getComparison()) {
            case '>':
                return $value > $threshold->getValue();
            case '<':
                return $value < $threshold->getValue();
            case '>=':
                return $value >= $threshold->getValue();
            case '<=':
                return $value <= $threshold->getValue();
            default:
                return false;
        }
    }
}
