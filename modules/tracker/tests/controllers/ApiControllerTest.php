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

require_once BASE_PATH.'/modules/api/tests/controllers/CallMethodsTestCase.php';

/** API test for tracker module. */
class Tracker_ApiControllerTest extends Api_CallMethodsTestCase
{
    public $moduleName = 'tracker';

    /** Setup. */
    public function setUp()
    {
        $this->enabledModules = array('api', 'scheduler', $this->moduleName);
        $this->_models = array('Assetstore', 'Community', 'Setting', 'User');

        $this->setupDatabase(array('default'));

        ControllerTestCase::setUp();
    }

    /**
     * Test uploading a scalar with a submission attached via uuid.
     *
     * @throws Zend_Exception
     */
    public function testUploadScalarWithSubmission()
    {
        $uuidComponent = MidasLoader::loadComponent('Uuid');
        $uuid = $uuidComponent->generate();

        $token = $this->_loginAsAdministrator();

        $outputs = array();
        $outputs['metric_0'] = $this->_submitScalar($token, $uuid, 'metric_0', '18');
        $outputs['metric_1'] = $this->_submitScalar($token, $uuid, 'metric_1', '19');
        $outputs['metric_2'] = $this->_submitScalar($token, $uuid, 'metric_2', '20');

        /** @var Tracker_SubmissionModel $submissionModel */
        $submissionModel = MidasLoader::loadModel('Submission', 'tracker');

        /** @var Tracker_SubmissionDao $submissionDao */
        $submissionDao = $submissionModel->getSubmission($uuid);
        $scalarDaos = $submissionModel->getScalars($submissionDao);

        /** @var Tracker_ScalarDao $scalarDao */
        foreach ($scalarDaos as $scalarDao) {
            $curOutput = $outputs[$scalarDao->getTrend()->getMetricName()];
            $this->assertEquals($curOutput->value, $scalarDao->getValue());
            $this->assertEquals($submissionDao->getKey(), $scalarDao->getSubmissionId());
        }
    }

    /**
     * Helper function to submit scalars.
     *
     * @param string $token the api token
     * @param string $uuid the uuid of the submission
     * @param string $metric the metric name of the trend
     * @param float $value the scalar value
     * @return mixed response object from the API
     */
    protected function _submitScalar($token, $uuid, $metric, $value)
    {
        $this->resetAll();
        $this->params['method'] = 'midas.tracker.scalar.add';
        $this->params['token'] = $token;
        $this->params['communityId'] = '2000';
        $this->params['producerDisplayName'] = 'Test Producer';
        $this->params['metricName'] = $metric;
        $this->params['value'] = $value;
        $this->params['producerRevision'] = 'deadbeef';
        $this->params['submitTime'] = 'now';
        $this->params['submissionUuid'] = $uuid;

        $res = $this->_callJsonApi();

        return $res->data;
    }
}
