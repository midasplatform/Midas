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
 * AggregateMetricSpec DAO for the tracker module.
 *
 * @method int getAggregateMetricSpecId()
 * @method void setAggregateMetricSpecId(int $aggregateMetricSpecId)
 * @method int getProducerId()
 * @method void setProducerId(int $producerId)
 * @method float getBranch()
 * @method void setBranch(float $branch)
 * @method float getName()
 * @method void setName(float $name)
 * @method float getDescription()
 * @method void setDescription(float $description)
 * @method float getSpec()
 * @method void setSpec(float $spec)
 * @method float getValue()
 * @method void setValue(float $value)
 * @method float getComparison()
 * @method void setComparison(float $comparison)
 * @method Tracker_ProducerDao getProducer()
 * @method void setProducer(Tracker_ProducerDao $producerDao)
 */
class Tracker_AggregateMetricSpecDao extends Tracker_AppDao
{
    /** @var string */
    public $_model = 'AggregateMetricSpec';

    /** @var string */
    public $_module = 'tracker';
}
