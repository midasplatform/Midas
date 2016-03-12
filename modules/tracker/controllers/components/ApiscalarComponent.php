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

/** API scalar component for the tracker module. */
class Tracker_ApiscalarComponent extends AppComponent
{
    /** @var string */
    public $moduleName = 'tracker';

    /**
     * Delete the given scalar.
     *
     * @path /tracker/scalar/{id}
     * @http DELETE
     * @param int id the id of the scalar to delete
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

        /** @var int $scalarId */
        $scalarId = $args['id'];

        /** @var Tracker_ScalarModel $scalarModel */
        $scalarModel = MidasLoader::loadModel('Scalar', $this->moduleName);

        /** @var Tracker_ScalarDao $scalarDao */
        $scalarDao = $scalarModel->load($scalarId);
        $userDao = $apihelperComponent->getUser($args);

        if ($scalarModel->policyCheck($scalarDao, $userDao, MIDAS_POLICY_ADMIN) === false) {
            throw new Exception('The scalar does not exist or you do not have the necessary permission', MIDAS_INVALID_POLICY);
        }

        $scalarModel->delete($scalarDao);
    }

    /**
     * Retrieve the given scalar.
     *
     * @path /tracker/scalar/{id}
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

        /** @var int $scalarId */
        $scalarId = $args['id'];

        /** @var Tracker_ScalarModel $scalarModel */
        $scalarModel = MidasLoader::loadModel('Scalar', $this->moduleName);

        /** @var Tracker_ScalarDao $scalarDao */
        $scalarDao = $scalarModel->load($scalarId);
        $userDao = $apihelperComponent->getUser($args);

        if ($scalarModel->policyCheck($scalarDao, $userDao, MIDAS_POLICY_READ) === false) {
            throw new Exception('The scalar does not exist or you do not have the necessary permission', MIDAS_INVALID_POLICY);
        }

        return $this->_toArray($scalarDao);
    }

    /**
     * TODO.
     *
     * @path /tracker/scalar
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
     * Create a new scalar.
     *
     * @path /tracker/scalar
     * @http POST
     * @param trend_id
     * @param submission_id
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
        $apihelperComponent->validateParams($args, array('trend_id'));
        $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_WRITE_DATA));

        /** @var int $trendId */
        $trendId = $args['trend_id'];

        /** @var Tracker_TrendModel $trendModel */
        $trendModel = MidasLoader::loadModel('Trend', $this->moduleName);

        /** @var Tracker_TrendDao $trendDao */
        $trendDao = $trendModel->load($trendId);
        $userDao = $apihelperComponent->getUser($args);

        if ($trendModel->policyCheck($trendDao, $userDao, MIDAS_POLICY_WRITE) === false) {
            throw new Exception('The trend does not exist or you do not have the necessary permission', MIDAS_INVALID_POLICY);
        }

        /** @var Tracker_ScalarModel $scalarModel */
        $scalarModel = MidasLoader::loadModel('Scalar', $this->moduleName);

        /** @var Tracker_ScalarDao $scalarDao */
        $scalarDao = $scalarModel->initDao('Scalar', $args, $this->moduleName);
        $scalarModel->save($scalarDao);

        /** @var Tracker_ScalarDao $scalarDao */
        $scalarDao = $scalarModel->load($scalarDao->getScalarId());

        return $this->_toArray($scalarDao);
    }

    /**
     * Update the given scalar.
     *
     * @path /tracker/scalar/{id}
     * @http PUT
     * @param id
     * @param trend_id
     * @param submission_id (Optional)
     * @param value (Optional)
     * @return array
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function put($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->validateParams($args, array('id', 'trend_id'));
        $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_WRITE_DATA));

        /** @var int $scalarId */
        $scalarId = $args['id'];

        /** @var Tracker_ScalarModel $scalarModel */
        $scalarModel = MidasLoader::loadModel('Scalar', $this->moduleName);

        /** @var Tracker_ScalarDao $scalarDao */
        $scalarDao = $scalarModel->load($scalarId);
        $userDao = $apihelperComponent->getUser($args);

        if ($scalarModel->policyCheck($scalarDao, $userDao, MIDAS_POLICY_WRITE) === false) {
            throw new Exception('The scalar does not exist or you do not have the necessary permission', MIDAS_INVALID_POLICY);
        }

        /** @var Tracker_ScalarDao $scalarDao */
        $scalarDao = $scalarModel->initDao('Scalar', $args, $this->moduleName);
        $scalarDao->setScalarId($scalarId);
        $scalarModel->save($scalarDao);

        /** @var Tracker_ScalarDao $scalarDao */
        $scalarDao = $scalarModel->load($scalarId);

        return $this->_toArray($scalarDao);
    }

    /**
     * Convert the given scalar DAO to an array and prepend metadata.
     *
     * @param Tracker_ScalarDao $scalarDao scalar DAO
     * @return array associative array representation of the scalar DAO with metadata prepended
     */
    protected function _toArray($scalarDao)
    {
        $scalarArray = array(
            '_id' => $scalarDao->getKey(),
            '_type' => 'Tracker_Scalar',
        );

        return array_merge($scalarArray, $scalarDao->toArray());
    }
}
