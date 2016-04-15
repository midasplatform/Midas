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
