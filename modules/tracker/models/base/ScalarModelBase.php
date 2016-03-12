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

/** Scalar base model class for the tracker module. */
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
            'submission_id' => array('type' => MIDAS_DATA),
            'submit_time' => array('type' => MIDAS_DATA),   // Not in the DB, from submission
            'official' => array('type' => MIDAS_DATA),      // Not in the DB, from submission
            'value' => array('type' => MIDAS_DATA),
            'trend' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'Trend',
                'module' => $this->moduleName,
                'parent_column' => 'trend_id',
                'child_column' => 'trend_id',
            ),
            'submission' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'Submission',
                'module' => $this->moduleName,
                'parent_column' => 'submission_id',
                'child_column' => 'submission_id',
            )
        );

        $this->initialize();
    }

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
     * Return any other scalars from the same submission as the given scalar.
     *
     * @param Tracker_ScalarDao $scalarDao scalar DAO
     * @return array scalar DAOs
     */
    abstract public function getOtherScalarsFromSubmission($scalarDao);

    /**
     * Add a new scalar to the trend.
     *
     * @param Tracker_TrendDao $trendDao trend DAO
     * @param Tracker_SubmissionDao $submissionDao submission DAO
     * @param double $value
     * @return Tracker_ScalarDao scalar DAO
     */
    public function addToTrend($trendDao, $submissionDao, $value) {

        /** @var Tracker_ScalarDao $scalarDao */
        $scalarDao = MidasLoader::newDao('ScalarDao', $this->moduleName);

        $scalarDao->setSubmissionId($submissionDao->getKey());
        $scalarDao->setTrendId($trendDao->getKey());
        $scalarDao->setValue($value);
        $this->save($scalarDao);

        return $scalarDao;
    }

    /**
     * Check whether the given policy is valid for the given scalar and user.
     *
     * @param Tracker_ScalarDao $scalarDao scalar DAO
     * @param null|UserDao $userDao user DAO
     * @param int $policy policy
     * @return bool true if the given policy is valid for the given scalar and user
     */
    public function policyCheck($scalarDao, $userDao = null, $policy = MIDAS_POLICY_READ)
    {
        if (is_null($scalarDao) || $scalarDao === false) {
            return false;
        }

        /** @var Tracker_TrendModel $trendModel */
        $trendModel = MidasLoader::loadModel('Trend', $this->moduleName);
        $trendDao = $scalarDao->getTrend();

        return $trendModel->policyCheck($trendDao, $userDao, $policy);
    }
}
