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
 * @method string getName()
 * @method void setName(string $name)
 * @method string getDescription()
 * @method void setDescription(string $description)
 * @method string getSpec()
 * @method void setSpec(string $spec)
 * @method string getAbbreviation()
 * @method void setAbbreviation(string $abbreviation)
 * @method float getWarning()
 * @method void setWarning(float $warning)
 * @method float getFail()
 * @method void setFail(float $fail)
 * @method float getMin()
 * @method void setMin(float $min)
 * @method float getMax()
 * @method void setMax(float $max)
 * @method bool getLowerIsBetter()
 * @method void setLowerIsBetter(boolean $lowerIsBetter)
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
