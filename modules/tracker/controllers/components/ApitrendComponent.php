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
 * API trend component for the tracker module.
 *
 * @package Modules\Tracker\Component
 */
class Tracker_ApitrendComponent extends AppComponent
{
    /** @var string */
    public $moduleName = 'tracker';

    /**
     * Delete the given trend.
     *
     * @path /tracker/trend/{id}
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

        /** @var int $trendId */
        $trendId = $args['id'];

        /** @var Tracker_TrendModel $trendModel */
        $trendModel = MidasLoader::loadModel('Trend', $this->moduleName);

        /** @var Tracker_TrendDao $trendDao */
        $trendDao = $trendModel->load($trendId);
        $userDao = $apihelperComponent->getUser($args);

        if ($trendModel->policyCheck($trendDao, $userDao, MIDAS_POLICY_ADMIN) === false) {
            throw new Exception('The trend does not exist or you do not have the necessary permission', MIDAS_INVALID_POLICY);
        }

        $trendModel->delete($trendDao);
    }

    /**
     * Retrieve the given trend.
     *
     * @path /tracker/trend/{id}
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

        /** @var int $trendId */
        $trendId = $args['id'];

        /** @var Tracker_TrendModel $trendModel */
        $trendModel = MidasLoader::loadModel('Trend', $this->moduleName);

        /** @var Tracker_TrendDao $trendDao */
        $trendDao = $trendModel->load($trendId);
        $userDao = $apihelperComponent->getUser($args);

        if ($trendModel->policyCheck($trendDao, $userDao, MIDAS_POLICY_READ) === false) {
            throw new Exception('The trend does not exist or you do not have the necessary permission', MIDAS_INVALID_POLICY);
        }

        return $this->_toArray($trendDao);
    }

    /**
     * TODO.
     *
     * @path /tracker/trend
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
     * Create a new trend.
     *
     * @path /tracker/trend
     * @http POST
     * @param producer_id
     * @param metric_name (Optional)
     * @param display_name (Optional)
     * @param unit (Optional)
     * @param config_item_id (Optional)
     * @param test_dataset_id (Optional)
     * @param truth_dataset_id (Optional)
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

        /** @var Tracker_TrendModel $trendModel */
        $trendModel = MidasLoader::loadModel('Trend', $this->moduleName);

        /** @var Tracker_TrendDao $trendDao */
        $trendDao = $trendModel->initDao('Trend', $args, $this->moduleName);
        $trendModel->save($trendDao);

        /** @var Tracker_TrendDao $trendDao */
        $trendDao = $trendModel->load($trendDao->getTrendId());

        return $this->_toArray($trendDao);
    }

    /**
     * Update the given trend.
     *
     * @path /tracker/trend/{id}
     * @http PUT
     * @param id
     * @param producer_id
     * @param metric_name (Optional)
     * @param display_name (Optional)
     * @param unit (Optional)
     * @param config_item_id (Optional)
     * @param test_dataset_id (Optional)
     * @param truth_dataset_id (Optional)
     * @return array
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function put($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->validateParams($args, array('id', 'producer_id'));
        $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_WRITE_DATA));

        /** @var int $trendId */
        $trendId = $args['id'];

        /** @var Tracker_TrendModel $trendModel */
        $trendModel = MidasLoader::loadModel('Trend', $this->moduleName);

        /** @var Tracker_TrendDao $trendDao */
        $trendDao = $trendModel->load($trendId);
        $userDao = $apihelperComponent->getUser($args);

        if ($trendModel->policyCheck($trendDao, $userDao, MIDAS_POLICY_WRITE) === false) {
            throw new Exception('The trend does not exist or you do not have the necessary permission', MIDAS_INVALID_POLICY);
        }

        /** @var Tracker_TrendDao $trendDao */
        $trendDao = $trendModel->initDao('Trend', $args, $this->moduleName);
        $trendDao->setTrendId($trendId);
        $trendModel->save($trendDao);

        /** @var Tracker_TrendDao $trendDao */
        $trendDao = $trendModel->load($trendId);

        return $this->_toArray($trendDao);
    }

    /**
     * Convert the given trend DAO to an array and prepend metadata.
     *
     * @param Tracker_TrendDao $trendDao trend DAO
     * @return array associative array representation of the trend DAO with metadata prepended
     */
    protected function _toArray($trendDao)
    {
        $trendArray = array(
            '_id' => $trendDao->getKey(),
            '_type' => 'Tracker_Trend',
        );

        return array_merge($trendArray, $trendDao->toArray());
    }
}
