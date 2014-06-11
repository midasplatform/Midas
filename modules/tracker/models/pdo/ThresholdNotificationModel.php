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
 * ThresholdNotification PDO Model
 */
class Tracker_ThresholdNotificationModel extends Tracker_ThresholdNotificationModelBase
  {
  /**
   * Called when a scalar is submitted. Returns a list of notifications whose conditions were met.
   */
  public function getNotifications($scalar)
    {
    $sql = $this->database->select()
                          ->setIntegrityCheck(false)
                          ->where('trend_id = ?', $scalar->getTrend()->getKey());
    $rows = $this->database->fetchAll($sql);
    $thresholds = array();
    foreach($rows as $row)
      {
      $threshold = $this->initDao('ThresholdNotification', $row, $this->moduleName);
      if($this->testThreshold($scalar->getValue(), $threshold))
        {
        $thresholds[] = $threshold;
        }
      }
    return $thresholds;
    }

  /**
   * Return user threshold notification setting for the given trend, or null if none exists.
   */
  public function getUserSetting($user, $trend)
    {
    $sql = $this->database->select()
                ->setIntegrityCheck(false)
                ->where('trend_id= ?', $trend->getKey())
                ->where('action = ?', MIDAS_TRACKER_EMAIL_USER)
                ->where('recipient_id = ?', $user->getKey());
    return $this->initDao('ThresholdNotification', $this->database->fetchRow($sql), $this->moduleName);
    }

  /**
   * Delete all thresholds for the given trend
   */
  public function deleteByTrend($trend)
    {
    $sql = $this->database->select()
                ->setIntegrityCheck(false)
                ->where('trend_id= ?', $trend->getKey());
    $rows = $this->database->fetchAll($sql);
    foreach($rows as $row)
      {
      $threshold = $this->initDao('ThresholdNotification', $row, $this->moduleName);
      $this->delete($threshold);
      }
    }
  }
