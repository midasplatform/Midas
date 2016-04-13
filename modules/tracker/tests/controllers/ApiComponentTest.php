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

        // clean up
        $submissionModel->delete($submissionDao);
        $submissionModel->delete($submissionDao2);
        $submissionModel->delete($submissionDao3);
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
        $submissionDao->setBranch('master');
        $submissionModel->save($submissionDao);

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

        /** @var array $scalars */
        $scalars = array();

        /** @var Tracker_TrendDao $greedyError1TrendDao */
        $greedyError1TrendDao = $trendModel->load(1);
        $scalars[] = $scalarModel->addToTrend($greedyError1TrendDao, $submissionDao, 101);
        /** @var Tracker_TrendDao $greedyError2TrendDao */
        $greedyError2TrendDao = $trendModel->load(2);
        $scalars[] = $scalarModel->addToTrend($greedyError2TrendDao, $submissionDao, 102);
        /** @var Tracker_TrendDao $greedyError3TrendDao */
        $greedyError3TrendDao = $trendModel->load(3);
        $scalars[] = $scalarModel->addToTrend($greedyError3TrendDao, $submissionDao, 103);
        /** @var Tracker_TrendDao $greedyError4TrendDao */
        $greedyError4TrendDao = $trendModel->load(4);
        $scalars[] = $scalarModel->addToTrend($greedyError4TrendDao, $submissionDao, 104);

        // Get existing aggregate metrics for this submission, there should be 0.

        /** @var Tracker_AggregateMetricModel $aggregateMetricModel */
        $aggregateMetricModel = MidasLoader::loadModel('AggregateMetric', 'tracker');
        /** array $aggregateMetrics */
        $aggregateMetrics = $aggregateMetricModel->getAggregateMetricsForSubmission($submissionDao);

        // Add users 1 & 2 to notification 1, 95th percentile Greedy error > 19.0
        // Add users 1 & 3 to notification 2, 55th percentile Greedy error != 11.0

        /** @var Tracker_AggregateMetricNotificationModel $aggregateMetricNotificationModel */
        $aggregateMetricNotificationModel = MidasLoader::loadModel('AggregateMetricNotification', 'tracker');

        /** @var Tracker_AggregateMetricNotificationDao $amn95thPercentileGreedyError */
        $amn95thPercentileGreedyError = $aggregateMetricNotificationModel->load(1);
        /** @var Tracker_AggregateMetricNotificationDao $amn55thPercentileGreedyError */
        $amn55thPercentileGreedyError = $aggregateMetricNotificationModel->load(2);

        /** @var UserModel $userModel */
        $userModel = MidasLoader::loadModel('User');
        /** @var UserDao $user1Dao */
        $user1Dao = $userModel->load(1);
        /** @var UserDao $user2Dao */
        $user2Dao = $userModel->load(2);
        /** @var UserDao $user3Dao */
        $user3Dao = $userModel->load(3);

        $aggregateMetricNotificationModel->createUserNotification($amn95thPercentileGreedyError, $user1Dao);
        $aggregateMetricNotificationModel->createUserNotification($amn95thPercentileGreedyError, $user2Dao);

        $aggregateMetricNotificationModel->createUserNotification($amn55thPercentileGreedyError, $user1Dao);
        $aggregateMetricNotificationModel->createUserNotification($amn55thPercentileGreedyError, $user3Dao);

        // Sadly, enabling a module does not import its constants.
        require_once BASE_PATH.'/modules/scheduler/constant/module.php';
        /** @var Scheduler_JobModel $jobModel */
        $jobModel = MidasLoader::loadModel('Job', 'scheduler');
        // Ensure there aren't any notification jobs existing.
        $this->assertEquals(0, count($jobModel->findBy('task', 'TASK_TRACKER_SEND_AGGREGATE_METRIC_NOTIFICATION')));

        // Call the API to update and return metrics for this submission.

        $this->resetAll();
        $this->params['method'] = 'midas.tracker.aggregatemetrics.update';
        $this->params['token'] = $token;
        $this->params['uuid'] = $uuid;
        $this->params['notify'] = true;
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

        // Check that the correction notifications were scheduled.

        $expectedParams = array(
            array(
                'aggregate_metric_notification_id' => '1',
                'aggregate_metric_id' => '1',
                'recipient_id' => '1',
                'found' => false,
            ),
            array(
                'aggregate_metric_notification_id' => '1',
                'aggregate_metric_id' => '1',
                'recipient_id' => '2',
                'found' => false,
            ),
            array(
                'aggregate_metric_notification_id' => '2',
                'aggregate_metric_id' => '2',
                'recipient_id' => '1',
                'found' => false,
            ),
            array(
                'aggregate_metric_notification_id' => '2',
                'aggregate_metric_id' => '2',
                'recipient_id' => '3',
                'found' => false,
            ),
        );

        /** @var array $aggregateMetricNotificationJobs */
        $aggregateMetricNotificationJobs = $jobModel->findBy('task', 'TASK_TRACKER_SEND_AGGREGATE_METRIC_NOTIFICATION');
        $this->assertEquals(4, count($aggregateMetricNotificationJobs));
        /** @var Scheduler_JobDao $job */
        foreach ($aggregateMetricNotificationJobs as $job) {
            /** @var string $params */
            $params = $job->getParams();
            preg_match("/\"aggregate_metric_notification_id\":\"(\d+)\",\"aggregate_metric_id\":\"(\d+)\",\"recipient_id\":\"(\d+)\"/", $params, $matches);
            /** @var string $notificationId */
            $notificationId = $matches[1];
            /** @var string $metricId */
            $metricId = $matches[2];
            /** @var string $userId */
            $userId = $matches[3];

            /** @var int $ind */
            /** @var array $expectedParam */
            foreach ($expectedParams as $ind => $expectedParam) {
                if ($expectedParam['aggregate_metric_notification_id'] == $notificationId &&
                    $expectedParam['aggregate_metric_id'] == $metricId &&
                    $expectedParam['recipient_id'] == $userId) {
                        $expectedParams[$ind]['found'] = true;
                        break;
                }
            }
        }

        // Check that all the expected jobs were found.
        /** @var int $ind */
        /** @var array $expectedParam */
        foreach ($expectedParams as $ind => $expectedParam) {
            $this->assertTrue($expectedParam['found']);
        }

        // Clean up after the test so as to not interfere with other tests.

        // Delete all the jobs.
        /** @var Scheduler_JobDao $job */
        foreach ($aggregateMetricNotificationJobs as $job) {
            $jobModel->delete($job);
        }

        // Delete the created submission.
        $submissionModel->delete($submissionDao);

        // Delete the users associated to notifications.
        $aggregateMetricNotificationModel->deleteUserNotification($amn95thPercentileGreedyError, $user1Dao);
        $aggregateMetricNotificationModel->deleteUserNotification($amn95thPercentileGreedyError, $user2Dao);
        $aggregateMetricNotificationModel->deleteUserNotification($amn55thPercentileGreedyError, $user1Dao);
        $aggregateMetricNotificationModel->deleteUserNotification($amn55thPercentileGreedyError, $user3Dao);
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
        $this->params['method'] = 'midas.tracker.aggregatemetricspecnotifications.list';
        $this->params['token'] = $token;
        $this->params['aggregateMetricSpecId'] = 1;
        $resp = $this->_callJsonApi();
        /** @var array $notifiedUsers */
        $notifications = $resp->data;
        // Initialliy there is one notification with zero users.
        $this->assertEquals(1, count($notifications));
        $this->assertEquals(0, count($notifications[0]->users));
        /** @var Tracker_AggregateMetricNotificationModel $aggregateMetricNotificationModel */
        $aggregateMetricNotificationModel = MidasLoader::loadModel('AggregateMetricNotification', 'tracker');
        /** @var Tracker_AggregateMetricNotificationDao $notificationDao */
        $notificationDao = $aggregateMetricNotificationModel->initDao('AggregateMetricNotification', json_decode(json_encode($notifications[0]->notification), true), $this->moduleName);
        $this->assertEquals($notificationDao->getAggregateMetricSpecId(), 1);
        $this->assertEquals($notificationDao->getValue(), 19.0);
        $this->assertEquals($notificationDao->getComparison(), '>');
        $this->assertEquals($notificationDao->getBranch(), 'master');

        // Create one notified user.

        $this->resetAll();
        $this->params['method'] = 'midas.tracker.aggregatemetricspecnotifieduser.create';
        $this->params['token'] = $token;
        $this->params['userId'] = 1;
        $this->params['aggregateMetricNotificationId'] = $notificationDao->getAggregateMetricNotificationId();
        $resp = $this->_callJsonApi();
        $this->assertEquals(1, $resp->data->user_id);

        $this->resetAll();
        $this->params['method'] = 'midas.tracker.aggregatemetricspecnotifications.list';
        $this->params['token'] = $token;
        $this->params['aggregateMetricSpecId'] = 1;
        $resp = $this->_callJsonApi();
        /** @var array $notifications */
        $notifications = $resp->data;
        // There should be one user on this notification.
        $this->assertEquals(1, count($notifications));
        $this->assertEquals(1, count($notifications[0]->users));
        /** @var array $notifiedUsers */
        $notifiedUsers = $notifications[0]->users;
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
        /** @var Tracker_AggregateMetricNotificationDao $notificationDao */
        $notificationDao = $aggregateMetricNotificationModel->initDao('AggregateMetricNotification', json_decode(json_encode($notifications[0]->notification), true), $this->moduleName);
        $this->assertEquals($notificationDao->getAggregateMetricSpecId(), 1);
        $this->assertEquals($notificationDao->getValue(), 19.0);
        $this->assertEquals($notificationDao->getComparison(), '>');
        $this->assertEquals($notificationDao->getBranch(), 'master');

        // Create a notification with the same user.

        $this->resetAll();
        $this->params['method'] = 'midas.tracker.aggregatemetricspecnotifieduser.create';
        $this->params['token'] = $token;
        $this->params['userId'] = 1;
        $this->params['aggregateMetricNotificationId'] = 1;
        $resp = $this->_callJsonApi();

        $this->resetAll();
        $this->params['method'] = 'midas.tracker.aggregatemetricspecnotifications.list';
        $this->params['token'] = $token;
        $this->params['aggregateMetricSpecId'] = 1;
        $resp = $this->_callJsonApi();
        /** @var array $notifications */
        $notifications = $resp->data;
        /** @var array $notifiedUsers */
        $notifiedUsers = $notifications[0]->users;
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
        /** @var Tracker_AggregateMetricNotificationDao $notificationDao */
        $notificationDao = $aggregateMetricNotificationModel->initDao('AggregateMetricNotification', json_decode(json_encode($notifications[0]->notification), true), $this->moduleName);
        $this->assertEquals($notificationDao->getAggregateMetricSpecId(), 1);
        $this->assertEquals($notificationDao->getValue(), 19.0);
        $this->assertEquals($notificationDao->getComparison(), '>');
        $this->assertEquals($notificationDao->getBranch(), 'master');

        // Create a notification with a second user.

        $this->resetAll();
        $this->params['method'] = 'midas.tracker.aggregatemetricspecnotifieduser.create';
        $this->params['token'] = $token;
        $this->params['userId'] = 2;
        $this->params['aggregateMetricNotificationId'] = 1;
        $resp = $this->_callJsonApi();
        $this->assertEquals(2, $resp->data->user_id);

        $this->resetAll();
        $this->params['method'] = 'midas.tracker.aggregatemetricspecnotifications.list';
        $this->params['token'] = $token;
        $this->params['aggregateMetricSpecId'] = 1;
        $resp = $this->_callJsonApi();
        /** @var array $notifications */
        $notifications = $resp->data;
        /** @var array $notifiedUsers */
        $notifiedUsers = $notifications[0]->users;
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
        $this->params['aggregateMetricNotificationId'] = 1;
        $resp = $this->_callJsonApi();

        $this->resetAll();
        $this->params['method'] = 'midas.tracker.aggregatemetricspecnotifications.list';
        $this->params['token'] = $token;
        $this->params['aggregateMetricSpecId'] = 1;
        $resp = $this->_callJsonApi();
        /** @var array $notifications */
        $notifications = $resp->data;
        /** @var array $notifiedUsers */
        $notifiedUsers = $notifications[0]->users;
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
        $this->params['aggregateMetricNotificationId'] = 1;
        $resp = $this->_callJsonApi();
        $this->assertEquals(2, $resp->data->user_id);

        $this->resetAll();
        $this->params['method'] = 'midas.tracker.aggregatemetricspecnotifications.list';
        $this->params['token'] = $token;
        $this->params['aggregateMetricSpecId'] = 1;
        $resp = $this->_callJsonApi();
        /** @var array $notifications */
        $notifications = $resp->data;
        /** @var array $notifiedUsers */
        $notifiedUsers = $notifications[0]->users;
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
        $this->params['aggregateMetricNotificationId'] = 1;
        $resp = $this->_callJsonApi();
        $this->assertEquals(1, $resp->data->user_id);

        $this->resetAll();
        $this->params['method'] = 'midas.tracker.aggregatemetricspecnotifications.list';
        $this->params['token'] = $token;
        $this->params['aggregateMetricSpecId'] = 1;
        $resp = $this->_callJsonApi();
        /** @var array $notifications */
        $notifications = $resp->data;
        /** @var array $notifiedUsers */
        $notifiedUsers = $notifications[0]->users;
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
        $this->params['aggregateMetricNotificationId'] = 1;
        $resp = $this->_callJsonApi();

        $this->resetAll();
        $this->params['method'] = 'midas.tracker.aggregatemetricspecnotifications.list';
        $this->params['token'] = $token;
        $this->params['aggregateMetricSpecId'] = 1;
        $resp = $this->_callJsonApi();
        /** @var array $notifications */
        $notifications = $resp->data;
        /** @var array $notifiedUsers */
        $notifiedUsers = $notifications[0]->users;
        $this->assertEquals(0, count($notifiedUsers));
    }
}
