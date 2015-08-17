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

/** Trend base model class for the tracker module. */
abstract class Tracker_TrendModelBase extends Tracker_AppModel
{
    /** Constructor. */
    public function __construct()
    {
        parent::__construct();

        $this->_name = 'tracker_trend';
        $this->_key = 'trend_id';
        $this->_mainData = array(
            'trend_id' => array('type' => MIDAS_DATA),
            'producer_id' => array('type' => MIDAS_DATA),
            'metric_name' => array('type' => MIDAS_DATA),
            'display_name' => array('type' => MIDAS_DATA),
            'unit' => array('type' => MIDAS_DATA),
            'config_item_id' => array('type' => MIDAS_DATA),
            'test_dataset_id' => array('type' => MIDAS_DATA),
            'truth_dataset_id' => array('type' => MIDAS_DATA),
            'producer' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'Producer',
                'module' => $this->moduleName,
                'parent_column' => 'producer_id',
                'child_column' => 'producer_id',
            ),
            'config_item' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'Item',
                'parent_column' => 'config_item_id',
                'child_column' => 'item_id',
            ),
            'test_dataset_item' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'Item',
                'parent_column' => 'test_dataset_id',
                'child_column' => 'item_id',
            ),
            'truth_dataset_item' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'Item',
                'parent_column' => 'truth_dataset_id',
                'child_column' => 'item_id',
            ),
            'scalars' => array(
                'type' => MIDAS_ONE_TO_MANY,
                'model' => 'Scalar',
                'module' => $this->moduleName,
                'parent_column' => 'trend_id',
                'child_column' => 'trend_id',
            ),
        );

        $this->initialize();
    }

    /**
     * Return the trend DAO that matches the given the producer id, metric name, associated items, and unit.
     *
     * @param int $producerId producer id
     * @param string $metricName metric name
     * @param null|int $configItemId configuration item id
     * @param null|int $testDatasetId test dataset item id
     * @param null|int $truthDatasetId truth dataset item id
     * @param false|string $unit (Optional) scalar value unit, defaults to false
     * @return false|Tracker_TrendDao trend DAO or false if none exists
     */
    abstract public function getMatch($producerId, $metricName, $configItemId, $testDatasetId, $truthDatasetId, $unit = false);

    /**
     * Return the trend DAOs that match the given associative array of database columns and values.
     *
     * @param array $params associative array of database columns and values
     * @return array trend DAOs
     */
    abstract public function getAllByParams($params);

    /**
     * Return a chronologically ordered list of scalars for the given trend.
     *
     * @param Tracker_TrendDao $trendDao trend DAO
     * @param null|string $startDate start date
     * @param null|string $endDate end date
     * @param null|int $userId user id
     * @param null|string $branch branch name
     * @return array scalar DAOs
     */
    abstract public function getScalars($trendDao, $startDate = null, $endDate = null, $userId = null, $branch = null);

    /**
     * Return all trends corresponding to the given producer. They will be grouped by distinct
     * config/test/truth dataset combinations.
     *
     * @param Tracker_ProducerDao $producerDao producer DAO
     * @return array
     */
    abstract public function getTrendsGroupByDatasets($producerDao);

    /**
     * Save the given trend. Ensure that null values are explicitly set in the database.
     *
     * @param Tracker_TrendDao $trendDao trend DAO
     */
    public function save($trendDao)
    {
        $trendDao->setExplicitNullFields = true;

        parent::save($trendDao);
    }

    /**
     * Return the trend DAO that matches the given producer id, metric name, associated items, and unit, if the trend exists.
     * Otherwise, create the trend DAO.
     *
     * @param int $producerId producer id
     * @param string $metricName metric name
     * @param null|int $configItemId configuration item id
     * @param null|int $testDatasetId test dataset item id
     * @param null|int $truthDatasetId truth dataset item id
     * @param false|string $unit (Optional) scalar value unit, defaults to false
     * @return Tracker_TrendDao trend DAO
     */
    public function createIfNeeded($producerId, $metricName, $configItemId, $testDatasetId, $truthDatasetId, $unit = false)
    {
        $trendDao = $this->getMatch($producerId, $metricName, $configItemId, $testDatasetId, $truthDatasetId, $unit);

        if ($trendDao === false) {
            /** @var Tracker_TrendDao $trendDao */
            $trendDao = MidasLoader::newDao('TrendDao', $this->moduleName);
            $trendDao->setProducerId($producerId);
            $trendDao->setMetricName($metricName);
            $trendDao->setDisplayName($metricName);
            if ($unit === false) {
                $unit = '';
            }
            $trendDao->setUnit($unit);

            if (!is_null($configItemId)) {
                $trendDao->setConfigItemId($configItemId);
            }

            if (!is_null($testDatasetId)) {
                $trendDao->setTestDatasetId($testDatasetId);
            }

            if (!is_null($truthDatasetId)) {
                $trendDao->setTruthDatasetId($truthDatasetId);
            }

            $this->save($trendDao);
        }

        return $trendDao;
    }

    /**
     * Delete the given trend and all associated scalars.
     *
     * @param Tracker_TrendDao $trendDao trend DAO
     * @param null|ProgressDao $progressDao progress DAO
     */
    public function delete($trendDao, $progressDao = null)
    {
        /** @var Tracker_ScalarModel $scalarModel */
        $scalarModel = MidasLoader::loadModel('Scalar', $this->moduleName);

        /** @var Tracker_ThresholdNotificationModel $notificationModel */
        $notificationModel = MidasLoader::loadModel('ThresholdNotification', $this->moduleName);

        if (!is_null($progressDao)) {
            /** @var ProgressModel $progressModel */
            $progressModel = MidasLoader::loadModel('Progress');
            $progressDao->setMessage('Counting scalar points...');
            $progressModel->save($progressDao);
        }

        $scalarDaos = $trendDao->getScalars();
        $scalarIndex = 0;

        if (!is_null($progressDao)) {
            $progressDao->setMaximum(count($scalarDaos));

            /** @noinspection PhpUndefinedVariableInspection */
            $progressModel->save($progressDao);
        }

        /** @var Tracker_ScalarDao $scalarDao */
        foreach ($scalarDaos as $scalarDao) {
            if (!is_null($progressDao)) {
                ++$scalarIndex;
                $message = 'Deleting scalars: '.$scalarIndex.' of '.$progressDao->getMaximum();

                /** @noinspection PhpUndefinedVariableInspection */
                $progressModel->updateProgress($progressDao, $scalarIndex, $message);
            }

            $scalarModel->delete($scalarDao);
        }

        $notificationModel->deleteByTrend($trendDao);

        parent::delete($trendDao);
    }

    /**
     * Check whether the given policy is valid for the given trend and user.
     *
     * @param Tracker_TrendDao $trendDao trend DAO
     * @param null|UserDao $userDao user DAO
     * @param int $policy policy
     * @return bool true if the given policy is valid for the given trend and user
     */
    public function policyCheck($trendDao, $userDao = null, $policy = MIDAS_POLICY_READ)
    {
        if (is_null($trendDao) || $trendDao === false) {
            return false;
        }

        /** @var Tracker_ProducerModel $producerModel */
        $producerModel = MidasLoader::loadModel('Producer', $this->moduleName);
        $producerDao = $trendDao->getProducer();

        return $producerModel->policyCheck($producerDao, $userDao, $policy);
    }
}
