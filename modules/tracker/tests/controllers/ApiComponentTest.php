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
class Tracker_ApiComponentTest extends Api_CallMethodsTestCase
{
    public $moduleName = 'tracker';

    /** Setup. */
    public function setUp()
    {
        $this->setupDatabase(array('default'));
        $this->setupDatabase(array('default'), 'tracker'); // module dataset
        $this->setupDatabase(array('aggregateMetric'), 'tracker'); // module dataset

        $this->enabledModules = array('api', 'scheduler', $this->moduleName);
        $this->_models = array('Assetstore', 'Community', 'Setting', 'User');

        $db = Zend_Registry::get('dbAdapter');
        $configDatabase = Zend_Registry::get('configDatabase');
        if ($configDatabase->database->adapter == 'PDO_PGSQL') {
            $db->query("SELECT setval('tracker_trend_trend_id_seq', (SELECT MAX(trend_id) FROM tracker_trend)+1);");
            $db->query("SELECT setval('tracker_submission_submission_id_seq', (SELECT MAX(submission_id) FROM tracker_submission)+1);");
            $db->query("SELECT setval('tracker_scalar_scalar_id_seq', (SELECT MAX(scalar_id) FROM tracker_scalar)+1);");
        }

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
        $metric1Params = array('num_param' => 19.0, 'text_param' => 'metric1 text', 'null_param' => null);
        $outputs['metric_1'] = $this->_submitScalar($token, $uuid, 'metric_1', '19', 'meters', $metric1Params);
        $metric2Params = array('num_param' => 20.0, 'text_param' => 'metric2 text', 'null_param' => null);
        $outputs['metric_2'] = $this->_submitScalar($token, $uuid, 'metric_2', '20', 'mm', $metric2Params);

        /** @var Tracker_SubmissionModel $submissionModel */
        $submissionModel = MidasLoader::loadModel('Submission', 'tracker');

        /** @var Tracker_SubmissionDao $submissionDao */
        $submissionDao = $submissionModel->getSubmission($uuid);
        $scalarDaos = $submissionModel->getScalars($submissionDao);

        // Maps the scalars for each metric.
        $metricToScalar = array();

        /** @var Tracker_ScalarDao $scalarDao */
        foreach ($scalarDaos as $scalarDao) {
            $curOutput = $outputs[$scalarDao->getTrend()->getMetricName()];
            $metricToScalar[$scalarDao->getTrend()->getMetricName()] = $scalarDao;
            $this->assertEquals($curOutput->value, $scalarDao->getValue());
            $this->assertEquals($submissionDao->getKey(), $scalarDao->getSubmissionId());
        }

        // Params should be a zero element array here.
        $this->assertTrue(!($metricToScalar['metric_0']->getParams()));

        $metric1Params = $metricToScalar['metric_1']->getParams();
        $metric1ParamChecks = array(
            'num_param' => array('found' => false, 'type' => 'numeric', 'val' => 19.0),
            'text_param' => array('found' => false, 'type' => 'text', 'val' => 'metric1 text'),
            'null_param' => array('found' => false, 'type' => 'text', 'val' => ''),
        );

        // Test that the params are saved as the correct type and value.
        foreach ($metric1Params as $param) {
            $checks = $metric1ParamChecks[$param->getParamName()];
            $this->assertEquals($checks['type'], $param->getParamType());
            if ($checks['type'] === 'numeric') {
                $this->assertEquals($checks['val'], $param->getNumericValue());
            } else {
                $this->assertEquals($checks['val'], $param->getTextValue());
            }
            $metric1ParamChecks[$param->getParamName()]['found'] = true;
        }

        // Test that all params are tested.
        foreach ($metric1ParamChecks as $checks) {
            $this->assertTrue($checks['found']);
        }

        $metric2Params = $metricToScalar['metric_2']->getParams();
        $metric2ParamChecks = array(
            'num_param' => array('found' => false, 'type' => 'numeric', 'val' => 20.0),
            'text_param' => array('found' => false, 'type' => 'text', 'val' => 'metric2 text'),
            'null_param' => array('found' => false, 'type' => 'text', 'val' => ''),
        );

        // Test that the params are saved as the correct type and value.
        foreach ($metric2Params as $param) {
            $checks = $metric2ParamChecks[$param->getParamName()];
            $this->assertEquals($checks['type'], $param->getParamType());
            if ($checks['type'] === 'numeric') {
                $this->assertEquals($checks['val'], $param->getNumericValue());
            } else {
                $this->assertEquals($checks['val'], $param->getTextValue());
            }
            $metric2ParamChecks[$param->getParamName()]['found'] = true;
        }

        // Test that all params are tested.
        foreach ($metric2ParamChecks as $checks) {
            $this->assertTrue($checks['found']);
        }
    }

    /**
     * Helper function to submit scalars.
     *
     * @param string $token the api token
     * @param string $uuid the uuid of the submission
     * @param string $metric the metric name of the trend
     * @param float $value the scalar value
     * @param false|string $unit (Optional) the unit of the trend, defaults to false
     * @param false|array $scalarParams (Optional) the params of the scalar, defaults to false
     * @return mixed response object from the API
     */
    private function _submitScalar($token, $uuid, $metric, $value, $unit = false, $scalarParams = false)
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
        if ($unit !== false) {
            $this->params['unit'] = $unit;
        }
        if ($scalarParams !== false) {
            $this->params['params'] = json_encode($scalarParams);
        }
        $res = $this->_callJsonApi();

        return $res->data;
    }

    /**
     * Test listing the branch names tied to a producer and trend metric_name.
     *
     * @throws Zend_Exception
     */
    public function testBranchesformetricnameList()
    {
        $token = $this->_loginAsAdministrator();
        $this->resetAll();
        $this->params['method'] = 'midas.tracker.branchesformetricname.list';
        $this->params['token'] = $token;
        $this->params['producerId'] = '100';
        $this->params['trendMetricName'] = 'Greedy error';
        $res = $this->_callJsonApi();
        /** @var array branches */
        $branches = $res->data;
        $this->assertEquals(count($branches), 2);
        $this->assertTrue(in_array('master', $branches));
        $this->assertTrue(in_array('test', $branches));
    }
}
