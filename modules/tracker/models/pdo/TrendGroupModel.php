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

require_once BASE_PATH . '/modules/tracker/models/base/TrendgroupModelBase.php';

/** Trend Group model for the tracker module. */
class Tracker_TrendgroupModel extends Tracker_TrendgroupModelBase
{
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
    public function createIfNeeded($producerId, $configItemId, $testDatasetId, $truthDatasetId)
    {
        $sql = $this->database->select()->setIntegrityCheck(false
        )->where('producer_id = ?', $producerId);

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

        /** @var Tracker_TrendGroupDao $trendGroupDao */
        $trendgroupDao = $this->initDao('Trendgroup', $this->database->fetchRow($sql), $this->moduleName);

        if ($trendgroupDao === false) {

            $trendGroupDao = MidasLoader::newDao('TrendgroupDao', $this->moduleName);

            $trendGroupDao->setProducerId($producerId);

            if (!is_null($configItemId)) {
                $trendGroupDao->setConfigItemId($configItemId);
            }

            if (!is_null($testDatasetId)) {
                $trendGroupDao->setTestDatasetId($testDatasetId);
            }

            if (!is_null($truthDatasetId)) {
                $trendGroupDao->setTruthDatasetId($truthDatasetId);
            }

            $this->save($trendGroupDao);
        }
        return $trendgroupDao;
    }
}
