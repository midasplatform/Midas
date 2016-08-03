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

require_once BASE_PATH.'/modules/tracker/models/base/AggregateMetricSpecModelBase.php';

/** AggregateMetricSpec model for the tracker module. */
class Tracker_AggregateMetricSpecModel extends Tracker_AggregateMetricSpecModelBase
{
    /**
     * Create or update a Tracker AggregateMetricSpec, matching on the Producer
     * and spec fields.
     *
     * @param Tracker_ProducerDao $producerDao
     * @param string $name name of the aggregate metric
     * @param string $spec the spec for the aggregate metric spec
     * @param false|string $abbreviation name abbreviation for the threshold
     * @param false|string $description the description for the aggregate metric spec
     * @param false|float $warning warning value for this threshold
     * @param false|float $fail fail value for this threshold
     * @param false|float $min min value for display of this threshold
     * @param false|float $max max value for display of this threshold
     * @param false|bool $lowerIsBetter whether lower values are better for this threshold
     * @return false|Tracker_AggregateMetricSpecDao created from inputs
     */
    public function upsert(
        $producerDao,
        $name,
        $spec,
        $abbreviation = false,
        $description = false,
        $warning = false,
        $fail = false,
        $min = false,
        $max = false,
        $lowerIsBetter = false)
    {
        if (is_null($producerDao) || $producerDao === false) {
            return false;
        }
        $sql = $this->database->select()->setIntegrityCheck(false)
            ->where('producer_id = ?', $producerDao->getProducerId())
            ->where('spec = ?', $spec);
         /** @var Tracker_AggregateMetricSpecDao $aggregateMetricSpecDao */
         $aggregateMetricSpecDao = $this->initDao('AggregateMetricSpec', $this->database->fetchRow($sql), $this->moduleName);
        if ($aggregateMetricSpecDao === false) {
            $aggregateMetricSpecDao = MidasLoader::newDao('AggregateMetricSpecDao', $this->moduleName);
            $aggregateMetricSpecDao->setProducerId($producerDao->getProducerId());
            $aggregateMetricSpecDao->setSpec($spec);
        }
        $aggregateMetricSpecDao->setName($name);
        if ($abbreviation !== false) {
            $aggregateMetricSpecDao->setAbbreviation($abbreviation);
        }
        if ($description !== false) {
            $aggregateMetricSpecDao->setDescription($description);
        }
        if ($warning !== false) {
            $aggregateMetricSpecDao->setWarning($warning);
        }
        if ($fail !== false) {
            $aggregateMetricSpecDao->setFail($fail);
        }
        if ($min !== false) {
            $aggregateMetricSpecDao->setMin($min);
        }
        if ($max !== false) {
            $aggregateMetricSpecDao->setMax($max);
        }
        if ($lowerIsBetter !== false) {
            $aggregateMetricSpecDao->setLowerIsBetter($lowerIsBetter);
        }
        $this->save($aggregateMetricSpecDao);

        return $aggregateMetricSpecDao;
    }

    /**
     * Delete the given aggregate metric spec, any metrics calculated based on that spec,
     * and any associated notifications.
     *
     * @param Tracker_AggregateMetricSpecDao $aggregateMetricSpecDao aggregateMetricSpec DAO
     */
    public function delete($aggregateMetricSpecDao)
    {
        if (is_null($aggregateMetricSpecDao) || $aggregateMetricSpecDao === false) {
            return;
        }
        /** @var Tracker_AggregateMetricNotificationModel $aggregateMetricNotificationModel */
        $aggregateMetricNotificationModel = MidasLoader::loadModel('AggregateMetricNotification', 'tracker');
        $aggregateMetricNotifications = $aggregateMetricNotificationModel->findBy('aggregate_metric_spec_id', $aggregateMetricSpecDao->getAggregateMetricSpecId());
        /** @var Tracker_AggregateMetricNotificationDao $aggregateMetricNotificationDao */
        foreach ($aggregateMetricNotifications as $aggregateMetricNotificationDao) {
            $aggregateMetricNotificationModel->delete($aggregateMetricNotificationDao);
        }
        // Delete all associated metrics.
        $this->database->getDB()->delete('tracker_aggregate_metric', 'aggregate_metric_spec_id = '.$aggregateMetricSpecDao->getAggregateMetricSpecId());
        parent::delete($aggregateMetricSpecDao);
    }
}
