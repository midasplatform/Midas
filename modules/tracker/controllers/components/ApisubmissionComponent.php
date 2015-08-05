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
     * @param producer_id
     * @param uuid (Optional)
     * @param name (Optional)
     * @return array
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function post($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->requirePolicyScopes(
            array(MIDAS_API_PERMISSION_SCOPE_WRITE_DATA));
        $apihelperComponent->validateParams($args, array('producer_id'));

        $this->_checkUser($args,
                          'Only authenticated users can create submissions.');

        /** @var Tracker_SubmissionModel $submissionModel */
        $submissionModel = MidasLoader::loadModel('Submission',
                                                  $this->moduleName);

        if (!isset($args['uuid'])) {
            /** @var UuidComponent $uuidComponent */
            $uuidComponent = MidasLoader::loadComponent('UuidComponent');
            $args['uuid'] = $uuidComponent->generate();
        }

        /** @var Tracker_SubmissionDao $submissionDao */
        $submissionDao = $submissionModel->initDao('Submission',
                                                   $args,
                                                   $this->moduleName);

        // Catch violation of the unique constraint.
        try {
            $submissionModel->save($submissionDao);
        } catch (Zend_Db_Statement_Exception $e) {
            throw new Exception('That uuid is already in use.',
                                MIDAS_INVALID_PARAMETER);
        }

        $submissionId = $submissionDao->getSubmissionId();

        /** @var Tracker_SubmissionDao $submissionDao */
        $submissionDao = $submissionModel->load($submissionId);

        return $this->_toArray($submissionDao);
    }

    /**
     * Update the given submission.
     *
     * @path /tracker/submission/{id}
     * @http PUT
     * @param id
     * @param uuid (Optional)
     * @param name (Optional)
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
