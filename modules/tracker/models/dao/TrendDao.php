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

/**
 * Trend DAO for the tracker module.
 *
 * @method int getTrendId()
 * @method void setTrendId(int $trendId)
 * @method int getProducerId()
 * @method void setProducerId(int $producerId)
 * @method string getMetricName()
 * @method void setMetricName(string $metricName)
 * @method string getDisplayName()
 * @method void setDisplayName(string $displayName)
 * @method string getUnit()
 * @method void setUnit(string $unit)
 * @method int getConfigItemId()
 * @method void setConfigItemId(int $configItemId)
 * @method int getTestDatasetId()
 * @method void setTestDatasetId(int $testDatasetId)
 * @method int getTruthDatasetId()
 * @method void setTruthDatasetId(int $truthDatasetId)
 * @method Tracker_ProducerDao getProducer()
 * @method void setProducer(Tracker_ProducerDao $producerDao)
 * @method ItemDao getConfigItem()
 * @method void setConfigItem(ItemDao $configItemDao)
 * @method ItemDao getTestDatasetItem()
 * @method void setTestDatasetItem(ItemDao $testDatasetItemDao)
 * @method ItemDao getTruthDatasetItem()
 * @method void setTruthDatasetItem(ItemDao $truthDatasetItemDao)
 * @method array getScalars()
 * @method void setScalars(array $scalarDaos)
 * @method void setKeyMetric(int $keyMetric)
 */
class Tracker_TrendDao extends Tracker_AppDao
{
    /** @var string */
    public $_model = 'Trend';

    /** @var string */
    public $_module = 'tracker';

    /**
     * Alias for getKeyMetric.
     * @return mixed
     */
    public function isKeyMetric() {
       return $this->getKeyMetric();
    }
}
