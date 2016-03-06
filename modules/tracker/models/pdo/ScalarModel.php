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
        $sql = $this->database->select()->from('tracker_scalar')->join(
            'tracker_trend',
            'tracker_scalar.trend_id = tracker_trend.trend_id',
            array()
        )->where('tracker_scalar.submit_time = ?', $scalarDao->getSubmitTime())->where(
            'tracker_scalar.user_id = ?',
            $scalarDao->getUserId()
        )->where('tracker_trend.producer_id = ?', $scalarDao->getTrend()->getProducerId());
        $rows = $this->database->fetchAll($sql);
        $scalarDaos = array();

        /** @var Zend_Db_Table_Row_Abstract $row */
        foreach ($rows as $row) {
            $scalarDaos[] = $this->initDao('Scalar', $row, $this->moduleName);
        }

        return $scalarDaos;
    }

    /**
     * Return any other values from the same submission as the given scalar.
     *
     * @param Tracker_ScalarDao $scalarDao scalar DAO
     * @return array associative array with keys equal to the metric names
     */
    public function getOtherValuesFromSubmission($scalarDao)
    {
        $sql = $this->database->select()->setIntegrityCheck(false)->from(array('s' => 'tracker_scalar'))->join(
            array('t' => 'tracker_trend'),
            's.trend_id = t.trend_id'
        )->where('s.submit_time = ?', $scalarDao->getSubmitTime())->where(
            's.user_id = ?',
            $scalarDao->getUserId()
        )->where('t.producer_id = ?', $scalarDao->getTrend()->getProducerId())->order('metric_name ASC');
        $rows = $this->database->fetchAll($sql);
        $scalarDaos = array();

        /** @var Zend_Db_Table_Row_Abstract $row */
        foreach ($rows as $row) {
            $scalarDaos[$row['metric_name']] = array('value' => number_format((float) $row['value'], 4, '.', ''), 'unit' => $row['unit']);
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
        $this->database->getDB()->delete('tracker_scalar2item', 'scalar_id = '.$scalarDao->getKey());
        $this->database->getDB()->delete('tracker_param', 'scalar_id = '.$scalarDao->getKey());

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
        $sql = $this->database->select()->setIntegrityCheck(false)->where('trend_id = ?', $trendId)->where(
            'submit_time = ?',
            $submitTime
        );
        if (!is_null($userId)) {
            $sql->where('user_id = ?', $userId);
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
