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

/** Trend group base model class for the tracker module. */
abstract class Tracker_TrendgroupModelBase extends Tracker_AppModel
{
    /** Constructor. */
    public function __construct()
    {
        parent::__construct();

        $this->_name = 'tracker_trendgroup';
        $this->_key = 'trendgroup_id';
        $this->_mainData = array(
            'trendgroup_id' => array('type' => MIDAS_DATA),
            'producer_id' => array('type' => MIDAS_DATA),
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
            'trends' => array(
                'type' => MIDAS_ONE_TO_MANY,
                'model' => 'Trend',
                'module' => $this->moduleName,
                'parent_column' => 'trendgroup_id',
                'child_column' => 'trendgroup_id',
            ),
        );

        $this->initialize();
    }

    /**
     * Delete the trend group and all of its subordinate trends.
     *
     * @param Tracker_TrendgroupDao $dao
     */
    public function delete($dao)
    {
        /** @var Tracker_TrendModel $trendModel */
        $trendModel = MidasLoader::loadModel('Trend', $this->moduleName);
        $trends = $dao->getTrends();

        /** @var Tracker_TrendDao $trend */
        foreach ($trends as $trend) {
            $trendModel->delete($trend);
        }

        parent::delete($dao);
    }

    /**
     * Return the trendgroup DAO that matches the given producer id and associated item if the trendgroup exists.
     * Otherwise, create the trend DAO.
     *
     * @param int $producerId producer id
     * @param null|int $configItemId configuration item id
     * @param null|int $testDatasetId test dataset item id
     * @param null|int $truthDatasetId truth dataset item id
     * @return Tracker_TrendgroupDao trend DAO
     */
    abstract public function createIfNeeded($producerId, $configItemId, $testDatasetId, $truthDatasetId);
}
