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
        'user_id' => array('type' => MIDAS_DATA),
        'official' => array('type' => MIDAS_DATA),
        'build_results_url' => array('type' => MIDAS_DATA),
        'branch' => array('type' => MIDAS_DATA),
        'submit_time' => array('type' => MIDAS_DATA),
        'value' => array('type' => MIDAS_DATA),
        'producer_revision' => array('type' => MIDAS_DATA),
        'trend' => array('type' => MIDAS_MANY_TO_ONE,
                         'model' => 'Trend',
                         'module' => $this->moduleName,
                         'parent_column' => 'trend_id',
                         'child_column' => 'trend_id'),
        'user' => array('type' => MIDAS_MANY_TO_ONE,
                        'model' => 'User',
                        'parent_column' => 'user_id',
                        'child_column' => 'user_id')
      );
    $this->initialize();
    }

  abstract public function associateItem($scalar, $item, $label);
  abstract public function getAssociatedItems($scalar);
  abstract public function getOtherValuesFromSubmission($scalar);
  abstract public function getByTrendAndTimestamp($trendId, $timestamp, $user = null);
  abstract public function getDistinctBranches();

  /**
   * Add a new scalar point to the trend.  If overwrite is true, and a scalar
   * already exists on the trend with the same submit time and user, this will replace that scalar value.
   */
  public function addToTrend($trend, $submitTime, $producerRevision, $value, $user,
                             $overwrite = true, $official = true, $buildResultsUrl = '', $branch = '')
    {
    if($overwrite)
      {
      $dao = $this->getByTrendAndTimestamp($trend->getKey(), $submitTime, $user->getKey());
      if($dao)
        {
        $this->delete($dao);
        }
      }

    $scalar = MidasLoader::newDao('ScalarDao', $this->moduleName);
    $scalar->setTrendId($trend->getKey());
    $scalar->setSubmitTime($submitTime);
    $scalar->setProducerRevision($producerRevision);
    $scalar->setValue($value);
    $scalar->setUserId($user instanceof UserDao ? $user->getKey() : -1);
    $scalar->setOfficial($official ? 1 : 0);
    $scalar->setBuildResultsUrl($buildResultsUrl);
    $scalar->setBranch(trim($branch));

    $this->save($scalar);
    return $scalar;
    }
  }
