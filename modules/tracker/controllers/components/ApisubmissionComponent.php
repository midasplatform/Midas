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
 * API submission component for the tracker module.
 */
class Tracker_ApisubmissionComponent extends AppComponent
{
    /** @var string */
    public $moduleName = 'tracker';

    /**
     * Delete the given submission.
     *
     * @path /tracker/submission/{id}
     * @http DELETE
     * @param id
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

        $this->_checkUser($args,
                          'Only authenticated users can delete submissions.');

        /** @var int $submissionId */
        $submissionId = $args['id'];

        /** @var Tracker_SubmissionModel $submissionModel */
        $submissionModel = MidasLoader::loadModel('Submission',
                                                  $this->moduleName);

        /** @var Tracker_SubmissionDao $submissionDao */
        $submissionDao = $submissionModel->load($submissionId);

        if (is_null($submissionDao) || $submissionDao === false) {
            throw new Exception('A submission with id '.$submissionId.
                                ' does not exist.', MIDAS_NOT_FOUND);
        }

        $submissionModel->delete($submissionDao);
    }

    /**
     * Retrieve the given submission.
     *
     * @path /tracker/submission/{id}
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
        $apihelperComponent->requirePolicyScopes(
            array(MIDAS_API_PERMISSION_SCOPE_READ_DATA));

        /** @var int $submissionId */
        $submissionId = $args['id'];

        /** @var Tracker_SubmissionModel $submissionModel */
        $submissionModel = MidasLoader::loadModel('Submission',
                                                  $this->moduleName);

        /** @var Tracker_SubmissionDao $submissionDao */
        $submissionDao = $submissionModel->load($submissionId);

        if (is_null($submissionDao) || $submissionDao === false) {
            throw new Exception('A submission with id '.$submissionId.
                                ' does not exist.', MIDAS_NOT_FOUND);
        }

        return $this->_toArray($submissionDao);
    }

    /**
     * @todo
     *
     * @path /tracker/submission
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
        $apihelperComponent->requirePolicyScopes(
            array(MIDAS_API_PERMISSION_SCOPE_READ_DATA));

        // TODO: Implement index().

        return array();
    }

    /**
     * Create a new submission.
     *
     * @path /tracker/submission
     * @http POST
     * @param communityId
     * @param producerDisplayName
     * @param producerRevision
     * @param uuid (Optional)
     * @param name (Optional)
     * @param submitTime (Optional)
     * @param branch (Optional)
     * @param buildResultsUrl (Optional)
     * @param params (Optional)
     * @param extraUrls (Optional)
     * @param reproductionCommand (Optional)
     * @return array
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function post($args)
    {
        /** @var ApihelperComponent $apiHelperComponent */
        $apiHelperComponent = MidasLoader::loadComponent('Apihelper');

        /** @var CommunityModel $communityModel */
        $communityModel = MidasLoader::loadModel('Community');
        /** @var Tracker_SubmissionModel $submissionModel */
        $submissionModel = MidasLoader::loadModel('Submission',
            $this->moduleName);
        /** @var Tracker_ProducerModel $producerModel */
        $producerModel = MidasLoader::loadModel('Producer', $this->moduleName);

        $apiHelperComponent->requirePolicyScopes(
            array(MIDAS_API_PERMISSION_SCOPE_WRITE_DATA));
        $apiHelperComponent->validateParams($args, array('communityId', 'producerDisplayName', 'producerRevision'));

        $this->_checkUser($args,
                          'Only authenticated users can create submissions.');
        $user = $apiHelperComponent->getUser($args);


        /** @var CommunityDao $community */
        $community = $communityModel->load($args['communityId']);
        if (!$community || !$communityModel->policyCheck(
                $community,
                $user,
                MIDAS_POLICY_WRITE
            )
        ) {
            throw new Exception('Write permission required on community', 403);
        }

        $producerDisplayName = trim($args['producerDisplayName']);
        if ($producerDisplayName == '') {
            throw new Exception('Producer display name must not be empty', -1);
        }

        $producer = $producerModel->createIfNeeded($community->getKey(), $producerDisplayName);

        if (!isset($args['uuid'])) {
            /** @var UuidComponent $uuidComponent */
            $uuidComponent = MidasLoader::loadComponent('UuidComponent');
            $args['uuid'] = $uuidComponent->generate();
        }

        if (isset($args['extraUrls'])) {
            $args['extra_urls'] = json_decode($args['extraUrls'], true);
        }

        $args['build_results_url'] = isset($args['buildResultsUrl']) ? $args['buildResultsUrl'] : '';
        $args['branch'] = isset($args['branch']) ? $args['branch'] : '';
        $args['name'] = isset($args['name']) ? $args['name'] : '';
        $args['reproduction_command'] = isset($args['reproductionCommand']) ? $args['reproductionCommand'] : '';

        $submitTime = strtotime($args['submitTime']);
        if ($submitTime === false) {
            throw new Exception('Invalid submitTime value: '.$args['submitTime'], -1);
        }
        $submitTime = date('Y-m-d H:i:s', $submitTime);
        $args['submit_time'] = $submitTime;
        $args['producer_revision'] = trim($args['producerRevision']);

        $args['producer_id'] = $producer->getKey();

        // Remove params from the submission args for later insertion in param table
        if (isset($args['params']) && !is_null($args['params'])) {
            $params = $args['params'];
            unset($args['params']);
        }

        /** @var Tracker_SubmissionDao $submissionDao */
        $submissionDao = $submissionModel->initDao('Submission',
            $args,
            $this->moduleName);

        // Catch violation of the unique constraint.
        try {
            $submissionModel->save($submissionDao);
        } catch (Zend_Db_Statement_Exception $e) {
            throw new Exception('That uuid is already in use',
                                MIDAS_INVALID_PARAMETER);
        }

        if (isset($params) && is_string($params)) {
            $params = json_decode($params);
            $paramModel = MidasLoader::loadModel('Param', $this->moduleName);
            foreach ($params as $paramName => $paramValue) {
                /** @var Tracker_ParamDao $paramDao */
                $paramDao = MidasLoader::newDao('ParamDao', $this->moduleName);
                $paramDao->setSubmissionId($submissionDao->getKey());
                $paramDao->setParamName($paramName);
                $paramDao->setParamValue($paramValue);
                $paramModel->save($paramDao);
            }
        }

        return $this->_toArray($submissionDao);
    }

    /**
     * Update the given submission.
     *
     * @path /tracker/submission/{id}
     * @http PUT
     * @param id
     * @param name (Optional)
     * @param submitTime (Optional)
     * @param branch (Optional)
     * @param buildResultsUrl (Optional)
     * @param extraUrls (Optional)
     * @param reproductionCommand (Optional)
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
        $apihelperComponent->requirePolicyScopes(
            array(MIDAS_API_PERMISSION_SCOPE_WRITE_DATA));

        $this->_checkUser($args,
                          'Only authenticated users can edit submissions.');

        /** @var int $submissionId */
        $submissionId = $args['id'];

        /** @var Tracker_SubmissionModel $submissionModel */
        $submissionModel = MidasLoader::loadModel('Submission',
                                                  $this->moduleName);

        /** @var Tracker_SubmissionDao $submissionDao */
        $submissionDao = $submissionModel->load($submissionId);

        if (is_null($submissionDao) || $submissionDao === false) {
            throw new Exception('A submission with id '.$submissionId.
                                ' does not exist.', MIDAS_NOT_FOUND);
        }

        if (isset($args['submitTime'])) {
            $submitTime = strtotime($args['submitTime']);
            if ($submitTime === false) {
                throw new Exception('Invalid submitTime value: '.$args['submitTime'], -1);
            }
            $submitTime = date('Y-m-d H:i:s', $submitTime);
            $args['submitTime'] = $submitTime;
        }

        // Disallow modification of the uuid.
        if (isset($args['uuid'])) {
            unset($args['uuid']);
        }

        /** @var Tracker_SubmissionDao $submissionDao */
        $submissionDao = $submissionModel->initDao('Submission', $args,
                                                   $this->moduleName);
        $submissionDao->setSubmissionId($submissionId);
        $submissionModel->save($submissionDao);

        /** @var Tracker_SubmissionDao $submissionDao */
        $submissionDao = $submissionModel->load($submissionId);

        return $this->_toArray($submissionDao);
    }

    /**
     * Convert the given submission DAO to an array.
     *
     * @param Tracker_SubmissionDao $submissionDao submission DAO
     * @return array associative array representation of the submission DAO
     */
    protected function _toArray($submissionDao)
    {
        $submissionArray = array(
            '_id' => $submissionDao->getKey(),
            '_type' => 'Tracker_Submission',
        );

        return array_merge($submissionArray, $submissionDao->toArray());
    }

    /**
     * Check for authenticated user and throw exception otherwise.
     *
     * @param array $args arguments from the API call
     * @param string $msg exception message
     * @throws Exception
     * @return void
     */
    protected function _checkUser($args, $msg)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');

        if ($apihelperComponent->getUser($args) === false) {
            throw new Exception($msg, MIDAS_INVALID_POLICY);
        }
    }
}
