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
 * Scalar base model class for the tracker module.
 *
 * @package Modules\Tracker\Model
 */
abstract class Tracker_ScalarModelBase extends Tracker_AppModel
{
    /** Constructor. */
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
            'params' => array('type' => MIDAS_DATA),
            'extra_urls' => array('type' => MIDAS_DATA),
            'branch' => array('type' => MIDAS_DATA),
            'submit_time' => array('type' => MIDAS_DATA),
            'value' => array('type' => MIDAS_DATA),
            'producer_revision' => array('type' => MIDAS_DATA),
            'trend' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'Trend',
                'module' => $this->moduleName,
                'parent_column' => 'trend_id',
                'child_column' => 'trend_id',
            ),
            'user' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'User',
                'parent_column' => 'user_id',
                'child_column' => 'user_id',
            ),
        );
        $this->initialize();
    }

    /**
     * Associate the given scalar and item.
     *
     * @param Tracker_ScalarDao $scalar scalar DAO
     * @param ItemDao $item item DAO
     * @param string $label label
     */
    abstract public function associateItem($scalar, $item, $label);

    /**
     * Return the items associated with the given scalar.
     *
     * @param Tracker_ScalarDao $scalar scalar DAO
     * @return array array of associative arrays with keys "item" and "label"
     */
    abstract public function getAssociatedItems($scalar);

    /**
     * Return any other scalars from the same submission as the given scalar.
     *
     * @param Tracker_ScalarDao $scalar scalar DAO
     * @return array scalar DAOs
     */
    abstract public function getOtherScalarsFromSubmission($scalar);

    /**
     * Return any other values from the same submission as the given scalar.
     *
     * @param Tracker_ScalarDao $scalar scalar DAO
     * @return array associative array with keys equal to the metric names
     */
    abstract public function getOtherValuesFromSubmission($scalar);

    /**
     * Return a scalar given a trend id, submit time, and user id.
     *
     * @param int $trendId trend id
     * @param string $submitTime submit time
     * @param null|int $userId user id
     * @return false|Tracker_ScalarDao scalar DAO or false if none exists
     */
    abstract public function getByTrendAndTimestamp($trendId, $submitTime, $userId = null);

    /**
     * Return all distinct branch names of revisions producing scalars.
     *
     * @return array branch names
     */
    abstract public function getDistinctBranches();

    /**
     * Add a new scalar to the trend. If overwrite is true, and a scalar already exists on the trend with the same
     * submit time and user, then this will replace that scalar.
     *
     * @param Tracker_TrendDao $trend trend DAO
     * @param string $submitTime submit time
     * @param string $producerRevision producer revision
     * @param float $value scalar value
     * @param UserDao $user user DAO
     * @param bool $overwrite true if a scalar with the same trend, submit time, and user should be overwritten
     * @param bool $official true if the submission containing the scalar should be official
     * @param string $buildResultsUrl build results URL
     * @param null|string $branch branch name
     * @param null|string|array $params parameters
     * @param null|string|array $extraUrls extra URLs
     * @return Tracker_ScalarDao scalar DAO
     */
    public function addToTrend(
        $trend,
        $submitTime,
        $producerRevision,
        $value,
        $user,
        $overwrite = true,
        $official = true,
        $buildResultsUrl = '',
        $branch = '',
        $params = null,
        $extraUrls = null
    ) {
        if ($overwrite) {
            $dao = $this->getByTrendAndTimestamp($trend->getKey(), $submitTime, $user->getKey());
            if ($dao) {
                $this->delete($dao);
            }
        }

        if (is_array($params)) {
            $params = json_encode($params);
        }
        if (is_array($extraUrls)) {
            $extraUrls = json_encode($extraUrls);
        }

        /** @var Tracker_ScalarDao $scalar */
        $scalar = MidasLoader::newDao('ScalarDao', $this->moduleName);
        $scalar->setTrendId($trend->getKey());
        $scalar->setSubmitTime($submitTime);
        $scalar->setProducerRevision($producerRevision);
        $scalar->setValue($value);
        $scalar->setUserId($user instanceof UserDao ? $user->getKey() : -1);
        $scalar->setOfficial($official ? 1 : 0);
        $scalar->setBuildResultsUrl($buildResultsUrl);
        $scalar->setBranch(trim($branch));
        $scalar->setParams($params);
        $scalar->setExtraUrls($extraUrls);

        $this->save($scalar);

        return $scalar;
    }
}
