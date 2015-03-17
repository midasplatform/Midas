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
 * API producer component for the tracker module.
 *
 * @package Modules\Tracker\Component
 */
class Tracker_ApiproducerComponent extends AppComponent
{
    /** @var string */
    public $moduleName = 'tracker';

    /**
     * Delete the given producer.
     *
     * @path /tracker/producer/{id}
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

        /** @var int $producerId */
        $producerId = $args['id'];

        /** @var Tracker_ProducerModel $producerModel */
        $producerModel = MidasLoader::loadModel('Producer', $this->moduleName);

        /** @var Tracker_ProducerDao $producerDao */
        $producerDao = $producerModel->load($producerId);
        $userDao = $apihelperComponent->getUser($args);

        if ($producerModel->policyCheck($producerDao, $userDao, MIDAS_POLICY_ADMIN) === false) {
            throw new Exception('The producer does not exist or you do not have the necessary permission', MIDAS_INVALID_POLICY);
        }

        $producerModel->delete($producerDao);
    }

    /**
     * Retrieve the given producer.
     *
     * @path /tracker/producer/{id}
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

        /** @var int $producerId */
        $producerId = $args['id'];

        /** @var Tracker_ProducerModel $producerModel */
        $producerModel = MidasLoader::loadModel('Producer', $this->moduleName);

        /** @var Tracker_ProducerDao $producerDao */
        $producerDao = $producerModel->load($producerId);
        $userDao = $apihelperComponent->getUser($args);

        if ($producerModel->policyCheck($producerDao, $userDao, MIDAS_POLICY_READ) === false) {
            throw new Exception('The producer does not exist or you do not have the necessary permission', MIDAS_INVALID_POLICY);
        }

        return $this->_toArray($producerDao);
    }

    /**
     * TODO.
     *
     * @path /tracker/producer
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
     * Create a new producer.
     *
     * @path /tracker/producer
     * @http POST
     * @param community_id
     * @param repository (Optional)
     * @param revision_url (Optional)
     * @param executable_name (Optional)
     * @param display_name (Optional)
     * @param description (Optional)
     * @return array
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function post($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->validateParams($args, array('community_id'));
        $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_WRITE_DATA));

        /** @var int $communityId */
        $communityId = $args['community_id'];

        /** @var CommunityModel $communityModel */
        $communityModel = MidasLoader::loadModel('Community');

        /** @var CommunityDao $communityDao */
        $communityDao = $communityModel->load($communityId);
        $userDao = $apihelperComponent->getUser($args);

        if ($communityDao === false || $communityModel->policyCheck($communityDao, $userDao, MIDAS_POLICY_WRITE) === false) {
            throw new Exception('The community does not exist or you do not have the necessary permission', MIDAS_INVALID_POLICY);
        }

        /** @var Tracker_ProducerModel $producerModel */
        $producerModel = MidasLoader::loadModel('Producer', $this->moduleName);

        /** @var Tracker_ProducerDao $producerDao */
        $producerDao = $producerModel->initDao('Producer', $args, $this->moduleName);
        $producerModel->save($producerDao);

        /** @var Tracker_ProducerDao $producerDao */
        $producerDao = $producerModel->load($producerDao->getProducerId());

        return $this->_toArray($producerDao);
    }

    /**
     * Update the given producer.
     *
     * @path /tracker/producer/{id}
     * @http PUT
     * @param id
     * @param community_id
     * @param repository (Optional)
     * @param revision_url (Optional)
     * @param executable_name (Optional)
     * @param display_name (Optional)
     * @param description (Optional)
     * @return array
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function put($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->validateParams($args, array('id', 'community_id'));
        $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_WRITE_DATA));

        /** @var int $producerId */
        $producerId = $args['id'];

        /** @var Tracker_ProducerModel $producerModel */
        $producerModel = MidasLoader::loadModel('Producer', $this->moduleName);

        /** @var Tracker_ProducerDao $producerDao */
        $producerDao = $producerModel->load($producerId);
        $userDao = $apihelperComponent->getUser($args);

        if ($producerModel->policyCheck($producerDao, $userDao, MIDAS_POLICY_WRITE) === false) {
            throw new Exception('The producer does not exist or you do not have the necessary permission', MIDAS_INVALID_POLICY);
        }

        /** @var Tracker_ProducerDao $producerDao */
        $producerDao = $producerModel->initDao('Producer', $args, $this->moduleName);
        $producerDao->setProducerId($producerId);
        $producerModel->save($producerDao);

        /** @var Tracker_ProducerDao $producerDao */
        $producerDao = $producerModel->load($producerId);

        return $this->_toArray($producerDao);
    }

    /**
     * Convert the given producer DAO to an array and prepend metadata.
     *
     * @param Tracker_ProducerDao $producerDao producer DAO
     * @return array associative array representation of the producer DAO with metadata prepended
     */
    protected function _toArray($producerDao)
    {
        $producerArray = array(
            '_id' => $producerDao->getKey(),
            '_type' => 'Tracker_Producer',
        );

        return array_merge($producerArray, $producerDao->toArray());
    }
}
