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
 * Scalar Model Base
 */
abstract class Tracker_ScalarModelBase extends Tracker_AppModel
{
  /** constructor*/
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'tracker_scalar';
    $this->_key = 'scalar_id';
    $this->_mainData = array(
        'scalar_id' => array('type' => MIDAS_DATA),
        'trend_id' => array('type' => MIDAS_DATA),
        'submit_time' => array('type' => MIDAS_DATA),
        'value' => array('type' => MIDAS_DATA),
        'producer_revision' => array('type' => MIDAS_DATA),
        'trend' => array('type' => MIDAS_MANY_TO_ONE,
                         'model' => 'Trend',
                         'module' => $this->moduleName,
                         'parent_column' => 'trend_id',
                         'child_column' => 'trend_id')
      );
    $this->initialize();
    }

  public abstract function getAssociatedItems($scalar);
  public abstract function getOtherValuesFromSubmission($scalar);
  public abstract function deleteByTrendAndTimestamp($trendId, $timestamp);

  /**
   * Add a new scalar point to the trend.  If overwrite is true, and a scalar
   * already exists on the trend with the same submit time, this will replace that scalar value.
   */
  public function addToTrend($trend, $submitTime, $producerRevision, $value, $overwrite = true)
    {
    if($overwrite)
      {
      $this->deleteByTrendAndTimestamp($trend->getKey(), $submitTime);
      }
    
    $scalar = MidasLoader::newDao('ScalarDao', $this->moduleName);
    $scalar->setTrendId($trend->getKey());
    $scalar->setSubmitTime($submitTime);
    $scalar->setProducerRevision($producerRevision);
    $scalar->setValue($value);
    $this->save($scalar);
    return $scalar;
    }
}
