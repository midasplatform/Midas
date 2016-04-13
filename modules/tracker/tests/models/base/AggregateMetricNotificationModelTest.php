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

/** Test the AggregateMetricNotification model. */
class Tracker_AggregateMetricNotificationModelTest extends DatabaseTestCase
{
    /** Set up tests. */
    public function setUp()
    {
        $this->setupDatabase(array('default')); // core dataset
        $this->setupDatabase(array('aggregateMetric'), 'tracker'); // module dataset
        $this->enabledModules = array('scheduler', 'tracker');
        $this->_components = array('Json');

        parent::setUp();
    }

    /** tearDown tester method. */
    public function tearDown()
    {
        // Delete notified users.
        $db = Zend_Registry::get('dbAdapter');
        $db->delete('scheduler_job', "task = 'TASK_TRACKER_SEND_AGGREGATE_METRIC_NOTIFICATION'");
        parent::tearDown();
    }

    /** testUserNotifications */
    public function testUserNotifications()
    {
        /** @var Tracker_AggregateMetricNotificationModel $aggregateMetricNotificationModel */
        $aggregateMetricNotificationModel = MidasLoader::loadModel('AggregateMetricNotification', 'tracker');

        /** @var Tracker_AggregateMetricNotificationDao $amn95thPercentileGreedyError */
        $amn95thPercentileGreedyError = $aggregateMetricNotificationModel->load(1);
        /** @var Tracker_AggregateMetricNotificationDao $amn55thPercentileGreedyError */
        $amn55thPercentileGreedyError = $aggregateMetricNotificationModel->load(2);

        // At first there are no notified users.
        $this->assertEquals(0, count($aggregateMetricNotificationModel->getAllNotifiedUsers($amn95thPercentileGreedyError)));
        $this->assertEquals(0, count($aggregateMetricNotificationModel->getAllNotifiedUsers($amn55thPercentileGreedyError)));

        /** @var UserModel $userModel */
        $userModel = MidasLoader::loadModel('User');
        /** @var UserDao $user1Dao */
        $user1Dao = $userModel->load(1);
        /** @var UserDao $user2Dao */
        $user2Dao = $userModel->load(2);
        /** @var UserDao $user3Dao */
        $user3Dao = $userModel->load(3);

        // Add users to 95th percentile greedy error AMS.

        $expectedNotifiedUsers95thGreedyError = array(
            $user1Dao->getUserId() => false,
            $user2Dao->getUserId() => false
        );

        $aggregateMetricNotificationModel->createUserNotification($amn95thPercentileGreedyError, $user1Dao);
        $aggregateMetricNotificationModel->createUserNotification($amn95thPercentileGreedyError, $user2Dao);

        $actualNotifiedUsers95thGreedyError = $aggregateMetricNotificationModel->getAllNotifiedUsers($amn95thPercentileGreedyError);
        $this->assertEquals(count($expectedNotifiedUsers95thGreedyError), count($actualNotifiedUsers95thGreedyError));
        /** @var $UserDao $notifiedUser */
        foreach ($actualNotifiedUsers95thGreedyError as $notifiedUser) {
            $expectedNotifiedUsers95thGreedyError[$notifiedUser->getUserId()] = true;
        }
        // Ensure that the users tied to the notification are who we expect.
        /** @var $UserDao $expectedUser */
        /** @var bool $present */
        foreach ($expectedNotifiedUsers95thGreedyError as $notifiedUser => $present) {
            $this->assertTrue($present);
        }

        // Add a different set of users to 55th percentile greedy error AMS.

        $expectedNotifiedUsers55thGreedyError = array(
            $user1Dao->getUserId() => false,
            $user3Dao->getUserId() => false
        );

        $aggregateMetricNotificationModel->createUserNotification($amn55thPercentileGreedyError, $user1Dao);
        $aggregateMetricNotificationModel->createUserNotification($amn55thPercentileGreedyError, $user3Dao);

        $actualNotifiedUsers55thGreedyError = $aggregateMetricNotificationModel->getAllNotifiedUsers($amn55thPercentileGreedyError);
        $this->assertEquals(count($expectedNotifiedUsers55thGreedyError), count($actualNotifiedUsers55thGreedyError));
        /** @var $UserDao $notifiedUser */
        foreach ($actualNotifiedUsers55thGreedyError as $notifiedUser) {
            $expectedNotifiedUsers55thGreedyError[$notifiedUser->getUserId()] = true;
        }
        // Ensure that the users tied to the notification are who we expect.
        /** @var $UserDao $expectedUser */
        /** @var bool $present */
        foreach ($expectedNotifiedUsers55thGreedyError as $notifiedUser => $present) {
            $this->assertTrue($present);
        }

        // Test that scheduler jobs are created for notifications.

        /** @var Tracker_AggregateMetricSpecModel $aggregateMetricSpecModel */
        $aggregateMetricSpecModel = MidasLoader::loadModel('AggregateMetricSpec', 'tracker');
        /** @var Tracker_AggregateMetricSpecDao $greedyError95thPercentileAMSDao */
        $greedyError95thPercentileAMSDao = $aggregateMetricSpecModel->load(1);
        /** @var Tracker_AggregateMetricSpecDao $greedyError55thPercentileAMSDao */
        $greedyError55thPercentileAMSDao = $aggregateMetricSpecModel->load(2);

        /** @var Tracker_SubmissionModel $submissionModel */
        $submissionModel = MidasLoader::loadModel('Submission', 'tracker');

        // Submissions 1 and 2 are tied to branch 'master', submission 8 to 'test'.
        // Metrics should be calculated regardless of branch.
        // Notifications are tied to branches.

        /** @var Tracker_SubmissionDao $submission1Dao */
        $submission1Dao = $submissionModel->load(1);
        /** @var Tracker_SubmissionDao $submission2Dao */
        $submission2Dao = $submissionModel->load(2);
        /** @var Tracker_SubmissionDao $submission8Dao */
        $submission8Dao = $submissionModel->load(8);

        /** @var Tracker_AggregateMetricModel $aggregateMetricModel */
        $aggregateMetricModel = MidasLoader::loadModel('AggregateMetric', 'tracker');

        // Sadly, enabling a module does not import its constants.
        require_once BASE_PATH.'/modules/scheduler/constant/module.php';
        /** @var Scheduler_JobModel $jobModel */
        $jobModel = MidasLoader::loadModel('Job', 'scheduler');
        // Ensure there aren't any notification jobs existing.
        $this->assertEquals(0, count($jobModel->findBy('task', 'TASK_TRACKER_SEND_AGGREGATE_METRIC_NOTIFICATION')));

        /** @var Tracker_AggregateMetricDao $greedyError95thSubmission1Metric */
        $greedyError95thSubmission1Metric = $aggregateMetricModel->updateAggregateMetricForSubmission($greedyError95thPercentileAMSDao, $submission1Dao);
        $this->assertEquals($greedyError95thSubmission1Metric->getValue(), 19.0);
        $notificationJobs = $aggregateMetricNotificationModel->scheduleNotificationJobs($greedyError95thSubmission1Metric);
        $this->assertEquals(0, count($notificationJobs));

        $expectedNotifiedUsers95thGreedyError = array(
            $user1Dao->getUserId() => false,
            $user2Dao->getUserId() => false
        );

        /** @var Tracker_AggregateMetricDao $greedyError95thSubmission2Metric */
        $greedyError95thSubmission2Metric = $aggregateMetricModel->updateAggregateMetricForSubmission($greedyError95thPercentileAMSDao, $submission2Dao);
        $this->assertEquals($greedyError95thSubmission2Metric->getValue(), 38.0);
        $notificationJobs = $aggregateMetricNotificationModel->scheduleNotificationJobs($greedyError95thSubmission2Metric);
        $this->assertEquals(2, count($notificationJobs));
        foreach ($notificationJobs as $job) {
            preg_match("/\"recipient_id\":\"(\d+)\"/", $job->getParams(), $matches);
            $userId = $matches[1];
            $expectedNotifiedUsers95thGreedyError[$userId] = true;
        }
        // Ensure notifications are created for the correct users.
        /** @var $UserDao $notifiedUser */
        /** @var bool $present */
        foreach ($expectedNotifiedUsers95thGreedyError as $notifiedUser => $present) {
            $this->assertTrue($present);
        }

        /** @var Tracker_AggregateMetricDao $greedyError95thSubmission8Metric */
        $greedyError95thSubmission8Metric = $aggregateMetricModel->updateAggregateMetricForSubmission($greedyError95thPercentileAMSDao, $submission8Dao);
        $this->assertEquals($greedyError95thSubmission8Metric->getValue(), 654.0);
        // Even though the value is above threshold, it is not a branch that will notify.
        $this->assertEquals(0, count($aggregateMetricNotificationModel->scheduleNotificationJobs($greedyError95thSubmission8Metric)));

        /** @var Tracker_AggregateMetricDao $greedyError55thSubmission1Metric */
        $greedyError55thSubmission1Metric = $aggregateMetricModel->updateAggregateMetricForSubmission($greedyError55thPercentileAMSDao, $submission1Dao);
        $this->assertEquals($greedyError55thSubmission1Metric->getValue(), 11.0);
        $this->assertEquals(0, count($aggregateMetricNotificationModel->scheduleNotificationJobs($greedyError55thSubmission1Metric)));

        $expectedNotifiedUsers55thGreedyError = array(
            $user1Dao->getUserId() => false,
            $user3Dao->getUserId() => false
        );

        /** @var Tracker_AggregateMetricDao $greedyError55thSubmission2Metric */
        $greedyError55thSubmission2Metric = $aggregateMetricModel->updateAggregateMetricForSubmission($greedyError55thPercentileAMSDao, $submission2Dao);
        $this->assertEquals($greedyError55thSubmission2Metric->getValue(), 22.0);
        $notificationJobs = $aggregateMetricNotificationModel->scheduleNotificationJobs($greedyError55thSubmission2Metric);
        $this->assertEquals(2, count($notificationJobs));
        foreach ($notificationJobs as $job) {
            preg_match("/\"recipient_id\":\"(\d+)\"/", $job->getParams(), $matches);
            $userId = $matches[1];
            $expectedNotifiedUsers55thGreedyError[$userId] = true;
        }
        // Ensure notifications are created for the correct users.
        /** @var $UserDao $notifiedUser */
        /** @var bool $present */
        foreach ($expectedNotifiedUsers55thGreedyError as $notifiedUser => $present) {
            $this->assertTrue($present);
        }

        /** @var Tracker_AggregateMetricDao $greedyError55thSubmission8Metric */
        $greedyError55thSubmission8Metric = $aggregateMetricModel->computeAggregateMetricForSubmission($greedyError55thPercentileAMSDao, $submission8Dao);
        $this->assertEquals($greedyError55thSubmission8Metric->getValue(), 654.0);
        // Even though the value is above threshold, it is not a branch that will notify.
        $this->assertEquals(0, count($aggregateMetricNotificationModel->scheduleNotificationJobs($greedyError55thSubmission8Metric)));

        // Clean up after the test.
        $aggregateMetricModel->delete($greedyError95thSubmission1Metric);
        $aggregateMetricModel->delete($greedyError55thSubmission1Metric);

        // Ensure that removing a user from a notification actually removes them.
        $aggregateMetricNotificationModel->deleteUserNotification($amn95thPercentileGreedyError, $user1Dao);
        $aggregateMetricNotificationModel->deleteUserNotification($amn95thPercentileGreedyError, $user2Dao);
        $actualNotifiedUsers95thGreedyError = $aggregateMetricNotificationModel->getAllNotifiedUsers($amn95thPercentileGreedyError);
        $this->assertEquals(0, count($actualNotifiedUsers95thGreedyError));

        // Ensure that removing a user from a notification actually removes them.
        $aggregateMetricNotificationModel->deleteUserNotification($amn55thPercentileGreedyError, $user1Dao);
        $aggregateMetricNotificationModel->deleteUserNotification($amn55thPercentileGreedyError, $user3Dao);
        $actualNotifiedUsers55thGreedyError = $aggregateMetricNotificationModel->getAllNotifiedUsers($amn55thPercentileGreedyError);
        $this->assertEquals(0, count($actualNotifiedUsers55thGreedyError));
    }

    /** testDelete */
    public function testDelete()
    {
        /** @var Tracker_AggregateMetricNotificationModel $aggregateMetricNotificationModel */
        $aggregateMetricNotificationModel = MidasLoader::loadModel('AggregateMetricNotification', 'tracker');

        // Create a notification, tie 2 users to it, then delete it.
        $args = array(
            'aggregate_metric_spec_id' => 1,
            'branch' => 'blaster',
            'comparison' => '>',
            'value' => 1,
        );

        /** @var Tracker_AggregateMetricNotificationDao $aggregateMetricNotificationDao */
        $aggregateMetricNotificationDao = $aggregateMetricNotificationModel->initDao('AggregateMetricNotification', $args, 'tracker');
        $aggregateMetricNotificationModel->save($aggregateMetricNotificationDao);
        $amnId = $aggregateMetricNotificationDao->getAggregateMetricNotificationId();

        /** @var UserModel $userModel */
        $userModel = MidasLoader::loadModel('User');
        /** @var UserDao $user1Dao */
        $user1Dao = $userModel->load(1);
        /** @var UserDao $user2Dao */
        $user2Dao = $userModel->load(2);

        $aggregateMetricNotificationModel->createUserNotification($aggregateMetricNotificationDao, $user1Dao);
        $aggregateMetricNotificationModel->createUserNotification($aggregateMetricNotificationDao, $user2Dao);

        $aggregateMetricNotificationModel->delete($aggregateMetricNotificationDao);

        // Ensure the linked users are deleted.
        $db = Zend_Registry::get('dbAdapter');
        $row = $db->query('select count(*) as count from tracker_user2aggregate_metric_notification where aggregate_metric_notification_id = '. $amnId)->fetch();
        $this->assertEquals($row['count'], 0);
        $this->assertFalse($aggregateMetricNotificationModel->load($amnId));
    }
}
