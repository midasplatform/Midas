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

require_once BASE_PATH.'/modules/tracker/models/base/TrendModelBase.php';

/** Trend model for the tracker module. */
class Tracker_TrendModel extends Tracker_TrendModelBase
{
    /**
     * Return the trend DAO that matches the given the producer id, metric name, and associated items.
     *
     * @param int $producerId producer id
     * @param string $metricName metric name
     * @param null|int $configItemId configuration item id
     * @param null|int $testDatasetId test dataset item id
     * @param null|int $truthDatasetId truth dataset item id
     * @return false|Tracker_TrendDao trend DAO or false if none exists
     */
    public function getMatch($producerId, $metricName, $configItemId, $testDatasetId, $truthDatasetId)
    {
        $sql = $this->database->select()->setIntegrityCheck(false)->from(array('t' => 'tracker_trend')
        )->join(array('g' => 'tracker_trendgroup'), 't.trendgroup_id = g.trendgroup_id'
        )->where('g.producer_id = ?', $producerId)->where(
            't.metric_name = ?',
            $metricName
        );

        if (is_null($configItemId)) {
            $sql->where('g.config_item_id IS NULL');
        } else {
            $sql->where('g.config_item_id = ?', $configItemId);
        }

        if (is_null($truthDatasetId)) {
            $sql->where('g.truth_dataset_id IS NULL');
        } else {
            $sql->where('g.truth_dataset_id = ?', $truthDatasetId);
        }

        if (is_null($testDatasetId)) {
            $sql->where('g.test_dataset_id IS NULL');
        } else {
            $sql->where('g.test_dataset_id = ?', $testDatasetId);
        }

        return $this->initDao('Trend', $this->database->fetchRow($sql), $this->moduleName);
    }

    /**
     * Return a chronologically ordered list of scalars for the given trend.
     *
     * @param Tracker_TrendDao $trendDao trend DAO
     * @param null|string $startDate start date
     * @param null|string $endDate end date
     * @param null|int $userId user id
     * @param null|string|array $branch branch name
     * @return array scalar DAOs
     */
    public function getScalars($trendDao, $startDate = null, $endDate = null, $userId = null, $branch = null)
    {
        $sql = $this->database->select()->setIntegrityCheck(false)->from(array('s' => 'tracker_scalar')
        )->join(array('u' => 'tracker_submission'), 's.submission_id = u.submission_id'
        )->where(
            'trend_id = ?',
            $trendDao->getKey()
        )->order(array('u.submit_time ASC'));

        if (!is_null($startDate)) {
            $sql->where('u.submit_time >= ?', $startDate);
        }

        if (!is_null($endDate)) {
            $sql->where('u.submit_time <= ?', $endDate);
        }

        if (!is_null($branch)) {
            if (is_array($branch)) {
                $sql->where('u.branch IN (?)', $branch);
            } else {
                $sql->where('u.branch = ?', $branch);
            }
        }

        $scalarDaos = array();
        $rows = $this->database->fetchAll($sql);

        /** @var Zend_Db_Table_Row_Abstract $row */
        foreach ($rows as $row) {
            $scalarDaos[] = $this->initDao('Scalar', $row, $this->moduleName);
        }

        return $scalarDaos;
    }

    /**
     * Return all trends corresponding to the given producer. They will be grouped by their trend
     * group and returned along with the test, truth, and config item DAOs.
     *
     * @param Tracker_ProducerDao $producerDao producer DAO
     * @param bool $onlyKey whether to return only key trends
     * @return array array of associative arrays with keys "configItem", "testDataset", "truthDataset", and "trends"
     */
    public function getTrendsByGroup($producerDao, $onlyKey = false)
    {
        $sql = $this->database->select()->setIntegrityCheck(false)
            ->from('tracker_trendgroup')
            ->where('producer_id = ?', $producerDao->getKey());
        $rows = $this->database->fetchAll($sql);

        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');
        $results = array();

        /** @var Zend_Db_Table_Row_Abstract $row */
        foreach ($rows as $row) {
            $configItemDao = $row['config_item_id'] == null ? null : $itemModel->load($row['config_item_id']);
            $testDatasetItemDao = $row['test_dataset_id'] == null ? null : $itemModel->load($row['test_dataset_id']);
            $truthDatasetItemDao = $row['truth_dataset_id'] == null ? null : $itemModel->load($row['truth_dataset_id']);
            $result = array(
                'configItem' => $configItemDao,
                'testDataset' => $testDatasetItemDao,
                'truthDataset' => $truthDatasetItemDao,
            );
            $queryParams = array(
                't.trendgroup_id' => $row['trendgroup_id'],
            );
            if ($onlyKey !== false) {
                $queryParams['key_metric'] = '1';
            }
            $result['trends'] = $this->getAllByParams($queryParams);
            $results[] = $result;
        }

        return $results;
    }

    /**
     * Return the trend DAOs that match the given associative array of database columns and values.
     *
     * @param array $params associative array of database columns and values
     * @return array trend DAOs
     */
    public function getAllByParams($params)
    {
        $sql = $this->database->select()->from(array('t' => 'tracker_trend'))->join(array('g' => 'tracker_trendgroup'), 't.trendgroup_id=g.trendgroup_id')->setIntegrityCheck(false);

        /**
         * @var string $column
         * @var mixed $value
         */
        foreach ($params as $column => $value) {
            if (is_null($value)) {
                $sql->where($column.' IS NULL');
            } else {
                $sql->where($column.' = ?', $value);
            }
        }

        $sql->order('display_name ASC');
        $rows = $this->database->fetchAll($sql);
        $trendDaos = array();

        /** @var Zend_Db_Table_Row_Abstract $row */
        foreach ($rows as $row) {
            $trendDaos[] = $this->initDao('Trend', $row, $this->moduleName);
        }

        return $trendDaos;
    }
}
