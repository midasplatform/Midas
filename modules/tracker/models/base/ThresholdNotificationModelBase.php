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

/**
 * Threshold Notification Model Base
 */
abstract class Tracker_ThresholdNotificationModelBase extends Tracker_AppModel
{
  /** constructor*/
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
        'trend' => array('type' => MIDAS_MANY_TO_ONE,
                            'model' => 'Trend',
                            'module' => $this->moduleName,
                            'parent_column' => 'trend_id',
                            'child_column' => 'trend_id')
      );
    $this->initialize();
    }

  public abstract function getNotifications($scalar);
  public abstract function getUserSetting($user, $trend);
  public abstract function deleteByTrend($trend);

  /**
   * Check whether the given scalar value meets the threshold condition.
   * Returns true if the action should be taken, i.e. the threshold was crossed.
   */
  public function testThreshold($value, $threshold)
    {
    switch($threshold->getComparison())
      {
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
