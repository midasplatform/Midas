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
        $uuid2 = $uuidComponent->generate();
        $uuid3 = $uuidComponent->generate();

        $token = $this->_loginAsAdministrator();

        $metric1Params = array('num_param' => 19.0, 'text_param' => 'metric1 text', 'null_param' => null);
        $metric2Params = array('num_param' => 20.0, 'text_param' => 'metric2 text', 'null_param' => null);
        $this->_submitSubmission($token, $uuid);
        $this->_submitSubmission($token, $uuid2, $metric1Params);
        $this->_submitSubmission($token, $uuid3, $metric2Params);
        $outputs = array();
        $outputs['metric_0'] = $this->_submitScalar($token, $uuid, 'metric_0', '18');
        $outputs['metric_1'] = $this->_submitScalar($token, $uuid2, 'metric_1', '19', 'meters');
        $outputs['metric_2'] = $this->_submitScalar($token, $uuid3, 'metric_2', '20', 'mm');

        /** @var Tracker_SubmissionModel $submissionModel */
        $submissionModel = MidasLoader::loadModel('Submission', 'tracker');

        /** @var Tracker_SubmissionDao $submissionDao */
        $submissionDao = $submissionModel->getSubmission($uuid);

        /** @var Tracker_SubmissionDao $submissionDao2 */
        $submissionDao2 = $submissionModel->getSubmission($uuid2);

        /** @var Tracker_SubmissionDao $submissionDao3 */
        $submissionDao3 = $submissionModel->getSubmission($uuid3);

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
        $this->assertTrue(!($submissionDao->getParams()));

        $metric1Params = $submissionDao2->getParams();
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

        $metric2Params = $submissionDao3->getParams();
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

    private function _submitSubmission($token, $uuid, $params = false)
    {
        $this->resetAll();
        $this->params['method'] = 'midas.tracker.submission.add';
        $this->params['token'] = $token;
        $this->params['communityId'] = '2000';
        $this->params['producerDisplayName'] = 'Test Producer';
        $this->params['producerRevision'] = 'deadbeef';
        $this->params['submitTime'] = 'now';
        $this->params['uuid'] = $uuid;
        if ($params !== false) {
            $this->params['params'] = json_encode($params);
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

    /**
     * Test updating the aggregate metrics tied to a submission.
     *
     * @throws Zend_Exception
     */
    public function testAggregatemetricsUpdate()
    {
        // Create a new submission.
        $uuidComponent = MidasLoader::loadComponent('Uuid');
        $uuid = $uuidComponent->generate();
        /** @var Tracker_ProducerModel $producerModel */
        $producerModel = MidasLoader::loadModel('Producer', 'tracker');
        /** @var Tracker_ProducerDao $producerDao */
        $producerDao = $producerModel->load(100);
        /** @var Tracker_SubmissionModel $submissionModel */
        $submissionModel = MidasLoader::loadModel('Submission', 'tracker');
        $submissionModel->createSubmission($producerDao, $uuid, 'Tmp submission');
        /** @var Tracker_SubmissionDao $submissionDao */
        $submissionDao = $submissionModel->getSubmission($uuid);

        // Create 4 scalars on the submission.

        /** @var Tracker_TrendModel $trendModel */
        $trendModel = MidasLoader::loadModel('Trend', 'tracker');
        /** @var Tracker_ScalarModel $scalarModel */
        $scalarModel = MidasLoader::loadModel('Scalar', 'tracker');

        $token = $this->_loginAsAdministrator();
        /** @var AuthenticationComponent $authComponent */
        $authComponent = MidasLoader::loadComponent('Authentication');
        /** @var UserDao $userDao */
        $userDao = $authComponent->getUser(array('token' => $token), null);

        /** @var Tracker_TrendDao $greedyError1TrendDao */
        $greedyError1TrendDao = $trendModel->load(1);
        $scalarModel->addToTrend($greedyError1TrendDao, $submissionDao, 101);
        /** @var Tracker_TrendDao $greedyError2TrendDao */
        $greedyError2TrendDao = $trendModel->load(2);
        $scalarModel->addToTrend($greedyError2TrendDao, $submissionDao, 102);
        /** @var Tracker_TrendDao $greedyError3TrendDao */
        $greedyError3TrendDao = $trendModel->load(3);
        $scalarModel->addToTrend($greedyError3TrendDao, $submissionDao, 103);
        /** @var Tracker_TrendDao $greedyError4TrendDao */
        $greedyError4TrendDao = $trendModel->load(4);
        $scalarModel->addToTrend($greedyError4TrendDao, $submissionDao, 104);

        // Get existing aggregate metrics for this submission, there should be 0.

        /** @var Tracker_AggregateMetricModel $aggregateMetricModel */
        $aggregateMetricModel = MidasLoader::loadModel('AggregateMetric', 'tracker');
        /** array $aggregateMetrics */
        $aggregateMetrics = $aggregateMetricModel->getAggregateMetricsForSubmission($submissionDao);

        // Call the API to update and return metrics for this submission.

        $this->resetAll();
        $this->params['method'] = 'midas.tracker.aggregatemetrics.update';
        $this->params['token'] = $token;
        $this->params['uuid'] = $uuid;
        $resp = $this->_callJsonApi();
        $aggregateMetrics = array();
        /** stdClass $aggregateMetricStdClass */
        foreach ($resp->data as $aggregateMetricStdClass) {
            $aggregateMetrics[] = $aggregateMetricModel->initDao('AggregateMetric', json_decode(json_encode($aggregateMetricStdClass), true), $this->moduleName);
        }

        // Expect Greedy error [0, 55, 95] percentiles.

        $expectedMetricValues = array(
            array(104.0 => false),
            array(102.0 => false),
            array(101.0 => false),
        );
        /** @var Tracker_AggregateMetricDao $aggregateMetricDao */
        foreach ($aggregateMetrics as $aggregateMetricDao) {
            /** @var int $index */
            /** @var array $metricValue */
            foreach ($expectedMetricValues as $index => $metricValue) {
                if ($aggregateMetricDao->getValue() == key($metricValue)) {
                    $expectedMetricValues[$index] = array(key($metricValue) => true);
                }
            }
        }
        /** @var int $index */
        /** @var float $metricValue */
        foreach ($expectedMetricValues as $index => $metricValue) {
            $this->assertTrue($metricValue[key($metricValue)]);
        }

        // Now calling get on the model should return the correct metrics.

        /** array $aggregateMetrics */
        $aggregateMetrics = $aggregateMetricModel->getAggregateMetricsForSubmission($submissionDao);

        $expectedMetricValues = array(
            array(104.0 => false),
            array(102.0 => false),
            array(101.0 => false),
        );
        /** @var Tracker_AggregateMetricDao $aggregateMetricDao */
        foreach ($aggregateMetrics as $aggregateMetricDao) {
            /** @var int $index */
            /** @var array $metricValue */
            foreach ($expectedMetricValues as $index => $metricValue) {
                if ($aggregateMetricDao->getValue() == key($metricValue)) {
                    $expectedMetricValues[$index] = array(key($metricValue) => true);
                }
            }
        }
        /** @var int $index */
        /** @var float $metricValue */
        foreach ($expectedMetricValues as $index => $metricValue) {
            $this->assertTrue($metricValue[key($metricValue)]);
        }

        // Call delete on the submission so as to not interfere with other tests.
        $submissionModel->delete($submissionDao);
    }

    /**
     * Test creating, deleting and listing user notifications tied to a spec
     * using the three different API calls.
     *
     * @throws Zend_Exception
     */
    public function testAggregatemetricspecNotificationEndpoints()
    {
        // List the users with notifications on an AMS.

        $token = $this->_loginAsAdministrator();
        /** @var AuthenticationComponent $authComponent */
        $authComponent = MidasLoader::loadComponent('Authentication');
        /** @var UserDao $userDao */
        $userDao = $authComponent->getUser(array('token' => $token), null);

        $this->resetAll();
        $this->params['method'] = 'midas.tracker.aggregatemetricspecnotifiedusers.list';
        $this->params['token'] = $token;
        $this->params['aggregateMetricSpecId'] = 1;
        $resp = $this->_callJsonApi();
        /** @var array $notifiedUsers */
        $notifiedUsers = $resp->data;
        $this->assertEquals(0, count($notifiedUsers));

        // Create one notified user.

        $this->resetAll();
        $this->params['method'] = 'midas.tracker.aggregatemetricspecnotifieduser.create';
        $this->params['token'] = $token;
        $this->params['userId'] = 1;
        $this->params['aggregateMetricSpecId'] = 1;
        $resp = $this->_callJsonApi();
        $this->assertEquals(1, $resp->data->user_id);

        $this->resetAll();
        $this->params['method'] = 'midas.tracker.aggregatemetricspecnotifiedusers.list';
        $this->params['token'] = $token;
        $this->params['aggregateMetricSpecId'] = 1;
        $resp = $this->_callJsonApi();
        /** @var array $notifiedUsers */
        $notifiedUsers = $resp->data;
        /** @var array $expectedUserIds */
        $expectedUserIds = array(
            1 => false,
        );
        $this->assertEquals(count($expectedUserIds), count($notifiedUsers));
        /** @var stdClass $notifiedUser */
        foreach ($notifiedUsers as $notifiedUser) {
            $expectedUserIds[$notifiedUser->user_id] = true;
        }
        /** @var int $expectedUserId */
        foreach ($expectedUserIds as $expectedUserId) {
            $this->assertTrue($expectedUserIds[$expectedUserId]);
        }

        // Create a notification with the same user.

        $this->resetAll();
        $this->params['method'] = 'midas.tracker.aggregatemetricspecnotifieduser.create';
        $this->params['token'] = $token;
        $this->params['userId'] = 1;
        $this->params['aggregateMetricSpecId'] = 1;
        $resp = $this->_callJsonApi();

        $this->resetAll();
        $this->params['method'] = 'midas.tracker.aggregatemetricspecnotifiedusers.list';
        $this->params['token'] = $token;
        $this->params['aggregateMetricSpecId'] = 1;
        $resp = $this->_callJsonApi();
        /** @var array $notifiedUsers */
        $notifiedUsers = $resp->data;
        /** @var array $expectedUserIds */
        $expectedUserIds = array(
            1 => false,
        );
        $this->assertEquals(count($expectedUserIds), count($notifiedUsers));
        /** @var stdClass $notifiedUser */
        foreach ($notifiedUsers as $notifiedUser) {
            $expectedUserIds[$notifiedUser->user_id] = true;
        }
        /** @var int $expectedUserId */
        foreach ($expectedUserIds as $expectedUserId) {
            $this->assertTrue($expectedUserIds[$expectedUserId]);
        }

        // Create a notification with a second user.

        $this->resetAll();
        $this->params['method'] = 'midas.tracker.aggregatemetricspecnotifieduser.create';
        $this->params['token'] = $token;
        $this->params['userId'] = 2;
        $this->params['aggregateMetricSpecId'] = 1;
        $resp = $this->_callJsonApi();
        $this->assertEquals(2, $resp->data->user_id);

        $this->resetAll();
        $this->params['method'] = 'midas.tracker.aggregatemetricspecnotifiedusers.list';
        $this->params['token'] = $token;
        $this->params['aggregateMetricSpecId'] = 1;
        $resp = $this->_callJsonApi();
        /** @var array $notifiedUsers */
        $notifiedUsers = $resp->data;
        /** @var array $expectedUserIds */
        $expectedUserIds = array(
            1 => false,
            2 => false,
        );
        $this->assertEquals(count($expectedUserIds), count($notifiedUsers));
        /** @var stdClass $notifiedUser */
        foreach ($notifiedUsers as $notifiedUser) {
            $expectedUserIds[$notifiedUser->user_id] = true;
        }
        /** @var int $expectedUserId */
        foreach ($expectedUserIds as $expectedUserId) {
            $this->assertTrue($expectedUserIds[$expectedUserId]);
        }

        // Create a notification with the third user.

        $this->resetAll();
        $this->params['method'] = 'midas.tracker.aggregatemetricspecnotifieduser.create';
        $this->params['token'] = $token;
        $this->params['userId'] = 3;
        $this->params['aggregateMetricSpecId'] = 1;
        $resp = $this->_callJsonApi();

        $this->resetAll();
        $this->params['method'] = 'midas.tracker.aggregatemetricspecnotifiedusers.list';
        $this->params['token'] = $token;
        $this->params['aggregateMetricSpecId'] = 1;
        $resp = $this->_callJsonApi();
        /** @var array $notifiedUsers */
        $notifiedUsers = $resp->data;
        /** @var array $expectedUserIds */
        $expectedUserIds = array(
            1 => false,
            2 => false,
            3 => false,
        );
        $this->assertEquals(count($expectedUserIds), count($notifiedUsers));
        /** @var stdClass $notifiedUser */
        foreach ($notifiedUsers as $notifiedUser) {
            $expectedUserIds[$notifiedUser->user_id] = true;
        }
        /** @var int $expectedUserId */
        foreach ($expectedUserIds as $expectedUserId) {
            $this->assertTrue($expectedUserIds[$expectedUserId]);
        }

        // Delete user 2 from notifications.

        $this->resetAll();
        $this->params['method'] = 'midas.tracker.aggregatemetricspecnotifieduser.delete';
        $this->params['token'] = $token;
        $this->params['userId'] = 2;
        $this->params['aggregateMetricSpecId'] = 1;
        $resp = $this->_callJsonApi();
        $this->assertEquals(2, $resp->data->user_id);

        $this->resetAll();
        $this->params['method'] = 'midas.tracker.aggregatemetricspecnotifiedusers.list';
        $this->params['token'] = $token;
        $this->params['aggregateMetricSpecId'] = 1;
        $resp = $this->_callJsonApi();
        /** @var array $notifiedUsers */
        $notifiedUsers = $resp->data;
        /** @var array $expectedUserIds */
        $expectedUserIds = array(
            1 => false,
            3 => false,
        );
        $this->assertEquals(count($expectedUserIds), count($notifiedUsers));
        /** @var stdClass $notifiedUser */
        foreach ($notifiedUsers as $notifiedUser) {
            $expectedUserIds[$notifiedUser->user_id] = true;
        }
        /** @var int $expectedUserId */
        /** @var bool $found */
        foreach ($expectedUserIds as $expectedUserId => $found) {
            $this->assertTrue($found);
        }

        // Delete user 1 from notifications.

        $this->resetAll();
        $this->params['method'] = 'midas.tracker.aggregatemetricspecnotifieduser.delete';
        $this->params['token'] = $token;
        $this->params['userId'] = 1;
        $this->params['aggregateMetricSpecId'] = 1;
        $resp = $this->_callJsonApi();
        $this->assertEquals(1, $resp->data->user_id);

        $this->resetAll();
        $this->params['method'] = 'midas.tracker.aggregatemetricspecnotifiedusers.list';
        $this->params['token'] = $token;
        $this->params['aggregateMetricSpecId'] = 1;
        $resp = $this->_callJsonApi();
        /** @var array $notifiedUsers */
        $notifiedUsers = $resp->data;
        /** @var array $expectedUserIds */
        $expectedUserIds = array(
            3 => false,
        );
        $this->assertEquals(count($expectedUserIds), count($notifiedUsers));
        /** @var stdClass $notifiedUser */
        foreach ($notifiedUsers as $notifiedUser) {
            $expectedUserIds[$notifiedUser->user_id] = true;
        }
        /** @var int $expectedUserId */
        /** @var bool $found */
        foreach ($expectedUserIds as $expectedUserId => $found) {
            $this->assertTrue($found);
        }

        // Delete user 3 from notifications.

        $this->resetAll();
        $this->params['method'] = 'midas.tracker.aggregatemetricspecnotifieduser.delete';
        $this->params['token'] = $token;
        $this->params['userId'] = 3;
        $this->params['aggregateMetricSpecId'] = 1;
        $resp = $this->_callJsonApi();

        $this->resetAll();
        $this->params['method'] = 'midas.tracker.aggregatemetricspecnotifiedusers.list';
        $this->params['token'] = $token;
        $this->params['aggregateMetricSpecId'] = 1;
        $resp = $this->_callJsonApi();
        /** @var array $notifiedUsers */
        $notifiedUsers = $resp->data;
        $this->assertEquals(0, count($notifiedUsers));
    }
}
