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

require_once BASE_PATH.'/modules/tracker/models/base/ScalarModelBase.php';

/**
 * Scalar model for the tracker module.
 *
 * @package Modules\Tracker\Model
 */
class Tracker_ScalarModel extends Tracker_ScalarModelBase
{
    /**
     * Associate the given scalar and item.
     *
     * @param Tracker_ScalarDao $scalar scalar DAO
     * @param ItemDao $item item DAO
     * @param string $label label
     */
    public function associateItem($scalar, $item, $label)
    {
        $data = array('scalar_id' => $scalar->getKey(), 'item_id' => $item->getKey(), 'label' => $label);
        $this->database->getDB()->insert('tracker_scalar2item', $data);
    }

    /**
     * Return the items associated with the given scalar.
     *
     * @param Tracker_ScalarDao $scalar scalar DAO
     * @return array array of associative arrays with keys "item" and "label"
     */
    public function getAssociatedItems($scalar)
    {
        $sql = $this->database->select()->setIntegrityCheck(false)->from('tracker_scalar2item')->where(
            'scalar_id = ?',
            $scalar->getKey()
        );
        $rows = $this->database->fetchAll($sql);
        $results = array();

        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');

        /** @var Zend_Db_Table_Row_Abstract $row */
        foreach ($rows as $row) {
            $item = $itemModel->load($row['item_id']);
            $results[] = array('label' => $row['label'], 'item' => $item);
        }
        usort(
            $results,
            function ($a, $b) {
                return strcmp($a['label'], $b['label']);
            }
        );

        return $results;
    }

    /**
     * Return any other scalars from the same submission as the given scalar.
     *
     * @param Tracker_ScalarDao $scalar scalar DAO
     * @return array scalar DAOs
     */
    public function getOtherScalarsFromSubmission($scalar)
    {
        $sql = $this->database->select()->from('tracker_scalar')->join(
            'tracker_trend',
            'tracker_scalar.trend_id = tracker_trend.trend_id',
            array()
        )->where('tracker_scalar.submit_time = ?', $scalar->getSubmitTime())->where(
            'tracker_scalar.user_id = ?',
            $scalar->getUserId()
        )->where('tracker_trend.producer_id = ?', $scalar->getTrend()->getProducerId());
        $rows = $this->database->fetchAll($sql);
        $scalars = array();

        /** @var Zend_Db_Table_Row_Abstract $row */
        foreach ($rows as $row) {
            $scalars[] = $this->initDao('Scalar', $row, $this->moduleName);
        }

        return $scalars;
    }

    /**
     * Return any other values from the same submission as the given scalar.
     *
     * @param Tracker_ScalarDao $scalar scalar DAO
     * @return array associative array with keys equal to the metric names
     */
    public function getOtherValuesFromSubmission($scalar)
    {
        $sql = $this->database->select()->setIntegrityCheck(false)->from(array('s' => 'tracker_scalar'))->join(
            array('t' => 'tracker_trend'),
            's.trend_id = t.trend_id'
        )->where('s.submit_time = ?', $scalar->getSubmitTime())->where(
            's.user_id = ?',
            $scalar->getUserId()
        )->where('t.producer_id = ?', $scalar->getTrend()->getProducerId())->order('metric_name ASC');
        $rows = $this->database->fetchAll($sql);
        $scalars = array();

        /** @var Zend_Db_Table_Row_Abstract $row */
        foreach ($rows as $row) {
            $scalars[$row['metric_name']] = $row['value'].' '.$row['unit'];
        }

        return $scalars;
    }

    /**
     * Delete the given scalar and any associations to items.
     *
     * @param Tracker_ScalarDao $scalar scalar DAO
     */
    public function delete($scalar)
    {
        $this->database->getDB()->delete('tracker_scalar2item', 'scalar_id = '.$scalar->getKey());
        parent::delete($scalar);
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
        if ($userId !== null) {
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
