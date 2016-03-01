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

/** API aggregatemetricspec component for the tracker module. */
class Tracker_ApiaggregatemetricspecComponent extends AppComponent
{
    /** @var string */
    public $moduleName = 'tracker';

    /**
     * Delete the given aggregateMetricSpec.
     *
     * @path /tracker/aggregatemetricspec/{id}
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

        /** @var int $aggregateMetricSpecId */
        $aggregateMetricSpecId = $args['id'];

        /** @var Tracker_AggregateMetricSpecModel $aggregateMetricSpecModel */
        $aggregateMetricSpecModel = MidasLoader::loadModel('AggregateMetricSpec', $this->moduleName);

        /** @var Tracker_AggregateMetricSpecDao $aggregateMetricSpecDao */
        $aggregateMetricSpecDao = $aggregateMetricSpecModel->load($aggregateMetricSpecId);
        $userDao = $apihelperComponent->getUser($args);

        if ($aggregateMetricSpecModel->policyCheck($aggregateMetricSpecDao, $userDao, MIDAS_POLICY_ADMIN) === false) {
            throw new Exception('The aggregateMetricSpec does not exist or you do not have the necessary permission', MIDAS_INVALID_POLICY);
        }

        $aggregateMetricSpecModel->delete($aggregateMetricSpecDao);
    }

    /**
     * Retrieve the given aggregateMetricSpec.
     *
     * @path /tracker/aggregatemetricspec/{id}
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

        /** @var int $aggregateMetricSpecId */
        $aggregateMetricSpecId = $args['id'];

        /** @var Tracker_AggregateMetricSpecModel $aggregateMetricSpecModel */
        $aggregateMetricSpecModel = MidasLoader::loadModel('AggregateMetricSpec', $this->moduleName);

        /** @var Tracker_AggregateMetricSpecDao $aggregateMetricSpecDao */
        $aggregateMetricSpecDao = $aggregateMetricSpecModel->load($aggregateMetricSpecId);
        $userDao = $apihelperComponent->getUser($args);

        if ($aggregateMetricSpecModel->policyCheck($aggregateMetricSpecDao, $userDao, MIDAS_POLICY_READ) === false) {
            throw new Exception('The aggregateMetricSpec does not exist or you do not have the necessary permission', MIDAS_INVALID_POLICY);
        }

        return $this->_toArray($aggregateMetricSpecDao);
    }

    /**
     * TODO.
     *
     * @path /tracker/aggregatemetricspec/{id}
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
     * Create a new aggregateMetricSpec.
     *
     * @path /tracker/aggregatemetricspec
     * @http POST
     * @param producer_id
     * @param branch
     * @param name
     * @param spec
     * @param description (Optional)
     * @param value (Optional)
     * @param comparison (Optional)
     * @return array
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function post($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->validateParams($args, array('producer_id'));
        $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_WRITE_DATA));

        /** @var int $producerId */
        $producerId = $args['producer_id'];

        /** @var Tracker_ProducerModel $producerModel */
        $producerModel = MidasLoader::loadModel('Producer', $this->moduleName);

        /** @var Tracker_ProducerDao $producerDao */
        $producerDao = $producerModel->load($producerId);
        $userDao = $apihelperComponent->getUser($args);

        if ($producerModel->policyCheck($producerDao, $userDao, MIDAS_POLICY_WRITE) === false) {
            throw new Exception('The producer does not exist or you do not have the necessary permission', MIDAS_INVALID_POLICY);
        }

        /** @var Tracker_AggregateMetricSpecModel $aggregateMetricSpecModel */
        $aggregateMetricSpecModel = MidasLoader::loadModel('AggregateMetricSpec', $this->moduleName);

        /** @var Tracker_AggregateMetricSpecDao $aggregateMetricSpecDao */
        $aggregateMetricSpecDao = $aggregateMetricSpecModel->initDao('AggregateMetricSpec', $args, $this->moduleName);
        $aggregateMetricSpecModel->save($aggregateMetricSpecDao);

        /** @var Tracker_AggregateMetricSpecDao $aggregateMetricSpecDao */
        $aggregateMetricSpecDao = $aggregateMetricSpecModel->load($aggregateMetricSpecDao->getAggregateMetricSpecId());

        return $this->_toArray($aggregateMetricSpecDao);
    }

    /**
     * Update the given aggregateMetricSpec.
     *
     * @path /tracker/aggregatemetricspec/{id}
     * @http PUT
     * @param id
     * @param producer_id (Optional)
     * @param branch (Optional)
     * @param name (Optional)
     * @param spec (Optional)
     * @param description (Optional)
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

        /** @var int $aggregateMetricSpecId */
        $aggregateMetricSpecId = $args['id'];

        /** @var Tracker_AggregateMetricSpecModel $aggregateMetricSpecModel */
        $aggregateMetricSpecModel = MidasLoader::loadModel('AggregateMetricSpec', $this->moduleName);

        /** @var Tracker_AggregateMetricSpecDao $aggregateMetricSpecDao */
        $aggregateMetricSpecDao = $aggregateMetricSpecModel->load($aggregateMetricSpecId);
        $userDao = $apihelperComponent->getUser($args);
        if ($aggregateMetricSpecModel->policyCheck($aggregateMetricSpecDao, $userDao, MIDAS_POLICY_WRITE) === false) {
            throw new Exception('The aggregateMetricSpec does not exist or you do not have the necessary permission', MIDAS_INVALID_POLICY);
        }

        /** @var Tracker_AggregateMetricSpecDao $aggregateMetricSpecDao */
        $aggregateMetricSpecDao = $aggregateMetricSpecModel->initDao('AggregateMetricSpec', $args, $this->moduleName);
        $aggregateMetricSpecDao->setAggregateMetricSpecId($aggregateMetricSpecId);
        $aggregateMetricSpecModel->save($aggregateMetricSpecDao);

        /** @var Tracker_AggregateMetricSpecDao $aggregateMetricSpecDao */
        $aggregateMetricSpecDao = $aggregateMetricSpecModel->load($aggregateMetricSpecId);

        return $this->_toArray($aggregateMetricSpecDao);
    }

    /**
     * Convert the given aggregateMetricSpec DAO to an array and prepend metadata.
     *
     * @param Tracker_AggregateMetricSpecDao $aggregateMetricSpecDao aggregateMetricSpec DAO
     * @return array associative array representation of the aggregateMetricSpec DAO with metadata prepended
     */
    protected function _toArray($aggregateMetricSpecDao)
    {
        $aggregateMetricSpecArray = array(
            '_id' => $aggregateMetricSpecDao->getKey(),
            '_type' => 'Tracker_AggregateMetricSpec',
        );

        return array_merge($aggregateMetricSpecArray, $aggregateMetricSpecDao->toArray());
    }
}
