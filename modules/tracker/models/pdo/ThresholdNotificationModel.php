<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
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
