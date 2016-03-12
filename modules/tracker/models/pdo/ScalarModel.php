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

require_once BASE_PATH.'/modules/tracker/models/base/ScalarModelBase.php';

/** Scalar model for the tracker module. */
class Tracker_ScalarModel extends Tracker_ScalarModelBase
{

    /**
     * Return any other scalars from the same submission as the given scalar.
     *
     * @param Tracker_ScalarDao $scalarDao scalar DAO
     * @return array scalar DAOs
     */
    public function getOtherScalarsFromSubmission($scalarDao)
    {
        $sql = $this->database->select()->from(array('s' => 'tracker_scalar'))->join(
            array('u' => 'tracker_submission'),
            'tracker_scalar.submission_id = tracker_submission.submission_id'
        )->where('s.submission_id = ?', $scalarDao->getSubmissionId());
        $rows = $this->database->fetchAll($sql);
        $scalarDaos = array();

        /** @var Zend_Db_Table_Row_Abstract $row */
        foreach ($rows as $row) {
            $scalarDaos[] = $this->initDao('Scalar', $row, $this->moduleName);
        }

        return $scalarDaos;
    }

    /**
     * Delete the given scalar and any associations to items.
     *
     * @param Tracker_ScalarDao $scalarDao scalar DAO
     */
    public function delete($scalarDao)
    {
        parent::delete($scalarDao);
    }

    /**
     * Return a scalar given a trend id, submit time, and user id.
     *
     * @param int $trendId trend id
     * @param string $submitTime submit time
     * @param null|int $userId user id
     * @return false|Tracker_ScalarDao scalar DAO or false if none exists
     */
    public function getByTrendAndTimestamp($trendId, $submitTime, $userId = null)
    {
        $sql = $this->database->select()->setIntegrityCheck(false)->from(array('s' => 'tracker_scalar'))->join(
            array('u' => 'tracker_submission'),
            's.submission_id=u.submission_id'
        )->where('s.trend_id = ?', $trendId
        )->where('u.submit_time = ?', $submitTime);
        if (!is_null($userId)) {
            $sql->where('u.user_id = ?', $userId);
        }

        return $this->initDao('Scalar', $this->database->fetchRow($sql), $this->moduleName);
    }

    /**
     * Return all distinct branch names of revisions producing scalars.
     *
     * @return array branch names
     */
    public function getDistinctBranches()
    {
        $sql = $this->database->select()->setIntegrityCheck(false)->from(
            array('s' => 'tracker_scalar'),
            'branch'
        )->distinct();
        $rows = $this->database->fetchAll($sql);
        $branches = array();

        /** @var Zend_Db_Table_Row_Abstract $row */
        foreach ($rows as $row) {
            $branches[] = $row['branch'];
        }

        return $branches;
    }
}
