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
 * AggregateMetricNotification DAO for the tracker module.
 *
 * @method int getAggregateMetricNotificationId()
 * @method void setAggregateMetricNotificationId(int $aggregateMetricNotificationId)
 * @method float getBranch()
 * @method void setBranch(string $branch)
 * @method float getValue()
 * @method void setValue(float $value)
 * @method float getComparison()
 * @method void setComparison(string $comparison)
 * @method Tracker_AggregateMetricSpecDao getAggregateMetricSpec()
 * @method void setAggregateMetricSpec(Tracker_AggregateMetricSpecDao $aggregateMetricSpecDao)
 */
class Tracker_AggregateMetricNotificationDao extends Tracker_AppDao
{
    /** @var string */
    public $_model = 'AggregateMetricNotification';

    /** @var string */
    public $_module = 'tracker';
}
