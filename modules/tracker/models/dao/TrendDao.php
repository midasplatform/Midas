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
 * @method int getTrendGroupId()
 * @method void setTrendGroupId(int $trendGroupId)
 * @method string getMetricName()
 * @method void setMetricName(string $metricName)
 * @method string getDisplayName()
 * @method void setDisplayName(string $displayName)
 * @method string getUnit()
 * @method void setUnit(string $unit)
 * @method array getScalars()
 * @method void setScalars(array $scalarDaos)
 * @method void setKeyMetric(int $keyMetric)
 * @method int getKeyMetric()
 * @method Tracker_TrendgroupDao getTrendgroup()
 * @method void setTrendgroup(Tracker_TrendgroupDao $trendgroup)
 */
class Tracker_TrendDao extends Tracker_AppDao
{
    /** @var string */
    public $_model = 'Trend';

    /** @var string */
    public $_module = 'tracker';

    /**
     * Alias for getKeyMetric.
     * @return bool
     */
    public function isKeyMetric()
    {
        return (int) $this->getKeyMetric() === 1;
    }
}
