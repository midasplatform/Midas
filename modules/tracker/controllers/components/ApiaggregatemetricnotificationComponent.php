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

/** API aggregatemetricnotification component for the tracker module. */
class Tracker_ApiaggregatemetricnotificationComponent extends AppComponent
{
    /** @var string */
    public $moduleName = 'tracker';

    /**
     * Delete the given aggregateMetricNotification.
     *
     * @path /tracker/aggregatemetricnotification/{id}
     * @http DELETE
     * @param id
     * @return void
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function delete($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->validateParams($args, array('id'));
        $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_ADMIN_DATA));

        /** @var int $aggregateMetricNotificationId */
        $aggregateMetricNotificationId = $args['id'];

        /** @var Tracker_AggregateMetricNotificationModel $aggregateMetricNotificationModel */
        $aggregateMetricNotificationModel = MidasLoader::loadModel('AggregateMetricNotification', $this->moduleName);
        /** @var Tracker_AggregateMetricSpecModel $aggregateMetricSpecModel */
        $aggregateMetricSpecModel = MidasLoader::loadModel('AggregateMetricSpec', $this->moduleName);

        /** @var Tracker_AggregateMetricNotificationDao $aggregateMetricNotificationDao */
        $aggregateMetricNotificationDao = $aggregateMetricNotificationModel->load($aggregateMetricNotificationId);
        $userDao = $apihelperComponent->getUser($args);
        if ($aggregateMetricSpecModel->policyCheck($aggregateMetricNotificationDao->getAggregateMetricSpec(), $userDao, MIDAS_POLICY_WRITE) === false) {
            throw new Exception('The aggregateMetricNotification does not exist or you do not have the necessary permission', MIDAS_INVALID_POLICY);
        }

        $aggregateMetricNotificationModel->delete($aggregateMetricNotificationDao);
    }

    /**
     * Retrieve the given aggregateMetricNotification.
     *
     * @path /tracker/aggregatemetricnotification/{id}
     * @http GET
     * @param id
     * @return array
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function get($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->validateParams($args, array('id'));
        $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_READ_DATA));

        /** @var int $aggregateMetricNotificationId */
        $aggregateMetricNotificationId = $args['id'];

        /** @var Tracker_AggregateMetricNotificationModel $aggregateMetricNotificationModel */
        $aggregateMetricNotificationModel = MidasLoader::loadModel('AggregateMetricNotification', $this->moduleName);
        /** @var Tracker_AggregateMetricSpecModel $aggregateMetricSpecModel */
        $aggregateMetricSpecModel = MidasLoader::loadModel('AggregateMetricSpec', $this->moduleName);

        /** @var Tracker_AggregateMetricNotificationDao $aggregateMetricNotificationDao */
        $aggregateMetricNotificationDao = $aggregateMetricNotificationModel->load($aggregateMetricNotificationId);
        $userDao = $apihelperComponent->getUser($args);
        if ($aggregateMetricSpecModel->policyCheck($aggregateMetricNotificationDao->getAggregateMetricSpec(), $userDao, MIDAS_POLICY_WRITE) === false) {
            throw new Exception('The aggregateMetricNotification does not exist or you do not have the necessary permission', MIDAS_INVALID_POLICY);
        }

        return $this->_toArray($aggregateMetricNotificationDao);
    }

    /**
     * TODO.
     *
     * @path /tracker/aggregatemetricnotification/{id}
     * @http GET
     * @return array
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function index($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->validateParams($args, array());
        $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_READ_DATA));

        // TODO: Implement index().

        return array();
    }

    /**
     * Create a new aggregateMetricNotification.
     *
     * @path /tracker/aggregatemetricnotification
     * @http POST
     * @param aggregate_metric_spec_id
     * @param branch
     * @param comparison
     * @param value
     * @return array
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function post($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->validateParams($args, array('aggregate_metric_spec_id', 'branch', 'comparison', 'value'));
        $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_WRITE_DATA));

        /** @var int $aggregateMetricSpecId */
        $aggregateMetricSpecId = $args['aggregate_metric_spec_id'];

        /** @var Tracker_AggregateMetricSpecModel $aggregateMetricSpecModel */
        $aggregateMetricSpecModel = MidasLoader::loadModel('AggregateMetricSpec', $this->moduleName);

        /** @var Tracker_AggregateMetricNotificationDao $aggregateMetricNotificationDao */
        $aggregateMetricSpecDao = $aggregateMetricSpecModel->load($aggregateMetricSpecId);
        $userDao = $apihelperComponent->getUser($args);
        if ($aggregateMetricSpecModel->policyCheck($aggregateMetricSpecDao, $userDao, MIDAS_POLICY_WRITE) === false) {
            throw new Exception('The aggregateMetricSpec does not exist or you do not have the necessary permission', MIDAS_INVALID_POLICY);
        }

        /** @var Tracker_AggregateMetricNotificationModel $aggregateMetricNotificationModel */
        $aggregateMetricNotificationModel = MidasLoader::loadModel('AggregateMetricNotification', $this->moduleName);
        /** @var Tracker_AggregateMetricNotificationDao $aggregateMetricNotificationDao */
        $aggregateMetricNotificationDao = $aggregateMetricNotificationModel->initDao('AggregateMetricNotification', $args, $this->moduleName);
        $aggregateMetricNotificationModel->save($aggregateMetricNotificationDao);

        return $this->_toArray($aggregateMetricNotificationDao);
    }

    /**
     * Update the given aggregateMetricNotification.
     *
     * @path /tracker/aggregatemetricnotification/{id}
     * @http PUT
     * @param id
     * @param branch(Optional)
     * @param value (Optional)
     * @param comparison (Optional)
     * @return array
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function put($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->validateParams($args, array('id'));
        $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_WRITE_DATA));

        /** @var int $aggregateMetricNotificationId */
        $aggregateMetricNotificationId = $args['id'];

        /** @var Tracker_AggregateMetricNotificationModel $aggregateMetricNotificationModel */
        $aggregateMetricNotificationModel = MidasLoader::loadModel('AggregateMetricNotification', $this->moduleName);
        /** @var Tracker_AggregateMetricSpecModel $aggregateMetricSpecModel */
        $aggregateMetricSpecModel = MidasLoader::loadModel('AggregateMetricSpec', $this->moduleName);

        /** @var Tracker_AggregateMetricNotificationDao $aggregateMetricNotificationDao */
        $aggregateMetricNotificationDao = $aggregateMetricNotificationModel->load($aggregateMetricNotificationId);
        $userDao = $apihelperComponent->getUser($args);
        if ($aggregateMetricSpecModel->policyCheck($aggregateMetricNotificationDao->getAggregateMetricSpec(), $userDao, MIDAS_POLICY_WRITE) === false) {
            throw new Exception('The aggregateMetricNotification does not exist or you do not have the necessary permission', MIDAS_INVALID_POLICY);
        }

        // Disallow modification of the aggregate_metric_spec_id.
        if (isset($args['aggregate_metric_spec_id'])) {
            unset($args['aggregate_metric_spec_id']);
        }

        /** @var string $name */
        /** @var mixed $option */
        foreach ($aggregateMetricNotificationModel->getMainData() as $name => $option) {
            if (isset($args[$name])) {
                $aggregateMetricNotificationDao->$name = $args[$name];
            }
        }
        $aggregateMetricNotificationModel->save($aggregateMetricNotificationDao);

        return $this->_toArray($aggregateMetricNotificationDao);
    }

    /**
     * Convert the given aggregateMetricNotification DAO to an array and prepend metadata.
     *
     * @param Tracker_AggregateMetricNotificationDao $aggregateMetricNotificationDao aggregateMetricNotification DAO
     * @return array associative array representation of the aggregateMetricNotification DAO with metadata prepended
     */
    protected function _toArray($aggregateMetricNotificationDao)
    {
        $aggregateMetricNotificationArray = array(
            '_id' => $aggregateMetricNotificationDao->getKey(),
            '_type' => 'Tracker_AggregateMetricNotification',
        );

        return array_merge($aggregateMetricNotificationArray, $aggregateMetricNotificationDao->toArray());
    }
}
