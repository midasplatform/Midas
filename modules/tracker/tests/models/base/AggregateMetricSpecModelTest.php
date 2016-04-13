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

/** Test the AggregateMetricSpec. */
class Tracker_AggregateMetricSpecModelTest extends DatabaseTestCase
{
    public $moduleName = 'tracker';

    /** Set up tests. */
    public function setUp()
    {
        $this->setupDatabase(array('default')); // core dataset
        $this->setupDatabase(array('aggregateMetric'), 'tracker'); // module dataset
        $this->enabledModules = array('tracker');
        parent::setUp();
    }

    /** test AggregateMetricSpecModel createAggregateMetricSpec function */
    public function testCreateAggregateMetricSpec()
    {
        /** @var AggregateMetricSpecModel $aggregateMetricSpecModel */
        $aggregateMetricSpecModel = MidasLoader::loadModel('AggregateMetricSpec', 'tracker');

        $name = '95th Percentile Greedy distance ';
        $spec = "percentile('Greedy distance', 95)";

        // Pass a null producer.
        $emptyProducerAMSDao = $aggregateMetricSpecModel->createAggregateMetricSpec(null, $name, $spec);
        $this->assertFalse($emptyProducerAMSDao);

        /** @var Tracker_ProducerModel $producerModel */
        $producerModel = MidasLoader::loadModel('Producer', 'tracker');
        /** @var Tracker_ProducerDao $producer100Dao */
        $producer100Dao = $producerModel->load(100);

        $validAMSDao = $aggregateMetricSpecModel->createAggregateMetricSpec($producer100Dao, $name, $spec);
        $this->assertEquals($validAMSDao->getName(), $name);
        $this->assertEquals($validAMSDao->getSpec(), $spec);
        $this->assertEquals($validAMSDao->getProducerId(), $producer100Dao->getProducerId());
    }

    /** test AggregateMetricSpecModel getAggregateMetricSpecsForProducer function */
    public function testGetAggregateMetricSpecsForProducer()
    {
        /** @var Tracker_ProducerModel $producerModel */
        $producerModel = MidasLoader::loadModel('Producer', 'tracker');
        /** @var Tracker_ProducerDao $producerDao */
        $producerDao = $producerModel->load(100);
        // From the test database, we expect ids [1, 6].
        $spec1Ids = array();
        /** @var int $id */
        foreach (range(1, 6) as $id) {
            $spec1Ids[$id] = false;
        }

        /** @var AggregateMetricSpecModel $aggregateMetricSpecModel */
        $aggregateMetricSpecModel = MidasLoader::loadModel('AggregateMetricSpec', 'tracker');
        $producerAggregateMetricSpecDaos = $aggregateMetricSpecModel->getAggregateMetricSpecsForProducer($producerDao);
        /** @var Tracker_AggregateMetricSpecDao $producerSpec */
        foreach ($producerAggregateMetricSpecDaos as $producerSpec) {
            if (array_key_exists($producerSpec->getAggregateMetricSpecId(), $spec1Ids)) {
                $spec1Ids[$producerSpec->getAggregateMetricSpecId()] = true;
            }
        }
        /** @var string $specId */
        /** @var bool $found */
        foreach ($spec1Ids as $specId => $found) {
            $this->assertTrue($found);
        }

        // Test with null producerDao.
        $producerAggregateMetricSpecDaos = $aggregateMetricSpecModel->getAggregateMetricSpecsForProducer(null);
        $this->assertFalse($producerAggregateMetricSpecDaos);
    }

    /** test AggregateMetricSpecModel getAggregateMetricSpecsForSubmission function */
    public function testGetAggregateMetricSpecsForSubmission()
    {
        /** @var Tracker_ProducerModel $producerModel */
        $producerModel = MidasLoader::loadModel('Producer', 'tracker');
        /** @var Tracker_ProducerDao $producerDao */
        $producerDao = $producerModel->load(100);
        // From the test database, we expect ids [1, 6].
        $spec1Ids = array();
        /** @var int $id */
        foreach (range(1, 6) as $id) {
            $spec1Ids[$id] = false;
        }

        // Check submission 1.

        /** @var Tracker_SubmissionModel $submissionModel */
        $submissionModel = MidasLoader::loadModel('Submission', 'tracker');
        /** @var Tracker_SubmissionDao $submissionDao1 */
        $submissionDao1 = $submissionModel->load(1);

        /** @var AggregateMetricSpecModel $aggregateMetricSpecModel */
        $aggregateMetricSpecModel = MidasLoader::loadModel('AggregateMetricSpec', 'tracker');
        $submissionAggregateMetricSpecDaos = $aggregateMetricSpecModel->getAggregateMetricSpecsForSubmission($submissionDao1);
        /** @var Tracker_AggregateMetricSpecDao $submissionSpec */
        foreach ($submissionAggregateMetricSpecDaos as $submissionSpec) {
            if (array_key_exists($submissionSpec->getAggregateMetricSpecId(), $spec1Ids)) {
                $spec1Ids[$submissionSpec->getAggregateMetricSpecId()] = true;
            }
        }
        /** @var string $specId */
        /** @var bool $found */
        foreach ($spec1Ids as $specId => $found) {
            $this->assertTrue($found);
        }

        // Check submission 2.

        /** @var Tracker_SubmissionDao $submissionDao2 */
        $submissionDao2 = $submissionModel->load(2);

        // From the test database, we expect ids [1, 6].
        $spec2Ids = array();
        /** @var int $id */
        foreach (range(1, 6) as $id) {
            $spec2Ids[$id] = false;
        }

        /** @var AggregateMetricSpecModel $aggregateMetricSpecModel */
        $aggregateMetricSpecModel = MidasLoader::loadModel('AggregateMetricSpec', 'tracker');
        $submissionAggregateMetricSpecDaos = $aggregateMetricSpecModel->getAggregateMetricSpecsForSubmission($submissionDao2);
        /** @var Tracker_AggregateMetricSpecDao $submissionSpec */
        foreach ($submissionAggregateMetricSpecDaos as $submissionSpec) {
            if (array_key_exists($submissionSpec->getAggregateMetricSpecId(), $spec2Ids)) {
                $spec2Ids[$submissionSpec->getAggregateMetricSpecId()] = true;
            }
        }
        /** @var string $specId */
        /** @var bool $found */
        foreach ($spec2Ids as $specId => $found) {
            $this->assertTrue($found);
        }

        // Test with null submissonDao.
        $submissionAggregateMetricSpecDaos = $aggregateMetricSpecModel->getAggregateMetricSpecsForSubmission(null);
        $this->assertFalse($submissionAggregateMetricSpecDaos);
    }

    /** test AggregateMetricSpecModel delete function */
    public function testAggregateMetricSpecDelete()
    {
        /** @var AggregateMetricSpecModel $aggregateMetricSpecModel */
        $aggregateMetricSpecModel = MidasLoader::loadModel('AggregateMetricSpec', 'tracker');
        /** @var Tracker_SubmissionModel $submissionModel */
        $submissionModel = MidasLoader::loadModel('Submission', 'tracker');
        /** @var Tracker_SubmissionDao $submission1Dao */
        $submission1Dao = $submissionModel->load(1);
        /** @var Tracker_SubmissionDao $submission2Dao */
        $submission2Dao = $submissionModel->load(2);

        // Delete all AMS on these submissions to start in a known state, which is
        // an incidental test of delete!
        /** @var array $aggregateMetricSpecDaos */
        $aggregateMetricSpecDaos = $aggregateMetricSpecModel->getAggregateMetricSpecsForSubmission($submission1Dao);
        /** @var AggregateMetricSpecDao $aggregateMetricSpecDao */
        foreach ($aggregateMetricSpecDaos as $aggregateMetricSpecDao) {
            $aggregateMetricSpecModel->delete($aggregateMetricSpecDao);
        }
        /** @var array $aggregateMetricSpecDaos */
        $aggregateMetricSpecDaos = $aggregateMetricSpecModel->getAggregateMetricSpecsForSubmission($submission2Dao);
        /** @var AggregateMetricSpecDao $aggregateMetricSpecDao */
        foreach ($aggregateMetricSpecDaos as $aggregateMetricSpecDao) {
            $aggregateMetricSpecModel->delete($aggregateMetricSpecDao);
        }

        $name = '34th Percentile Greedy error ';
        $spec = "percentile('Greedy error', 34)";

        /** @var Tracker_ProducerModel $producerModel */
        $producerModel = MidasLoader::loadModel('Producer', 'tracker');
        /** @var Tracker_ProducerDao $producer100Dao */
        $producer100Dao = $producerModel->load(100);

        /** @var AggregateMetricSpecDao $validAMSDao */
        $validAMSDao = $aggregateMetricSpecModel->createAggregateMetricSpec($producer100Dao, $name, $spec);

        // Create A notification, tied to 2 users.

        /** @var UserModel $userModel */
        $userModel = MidasLoader::loadModel('User');
        /** @var UserDao $user1Dao */
        $user1Dao = $userModel->load(1);
        /** @var UserDao $user2Dao */
        $user2Dao = $userModel->load(2);

        /** @var Tracker_AggregateMetricNotificationModel $aggregateMetricNotificationModel */
        $aggregateMetricNotificationModel = MidasLoader::loadModel('AggregateMetricNotification', 'tracker');

        $notificationProperties = array(
            'aggregate_metric_spec_id' => $validAMSDao->getAggregateMetricSpecId(),
            'branch' => 'notify 1',
            'comparison' => '==',
            'value' => '16.0',
        );
        /** @var Tracker_AggregateMetricNotificationDao $notification1Dao */
        $notification1Dao = $aggregateMetricNotificationModel->initDao('AggregateMetricNotification', $notificationProperties, $this->moduleName);
        $aggregateMetricNotificationModel->save($notification1Dao);
        $aggregateMetricNotificationModel->createUserNotification($notification1Dao, $user1Dao);
        $aggregateMetricNotificationModel->createUserNotification($notification1Dao, $user2Dao);

        $this->assertEquals(2, count($aggregateMetricNotificationModel->getAllNotifiedUsers($notification1Dao)));

        /** @var AggregateMetricSpecDao $cachedAMSDao */
        $cachedAMSDao = $aggregateMetricSpecModel->load($validAMSDao->getAggregateMetricSpecId());

        // Compute some aggregate metrics to check that they will be deleted.

        /** @var AggregateMetricModel $aggregateMetricModel */
        $aggregateMetricModel = MidasLoader::loadModel('AggregateMetric', 'tracker');

        /** @var AggregateMetricDao $aggregateMetricDao */
        $aggregateMetricDao = $aggregateMetricModel->updateAggregateMetricForSubmission($validAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 7.0);
        /** @var array $aggregateMetricDaos */
        $aggregateMetricDaos = $aggregateMetricModel->getAggregateMetricsForSubmission($submission1Dao);
        $this->assertEquals(1, count($aggregateMetricDaos));
        $this->assertEquals($aggregateMetricDaos[0]->getValue(), 7.0);
        $aggregateMetricDao = $aggregateMetricModel->updateAggregateMetricForSubmission($validAMSDao, $submission2Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 14.0);
        $aggregateMetricDaos = $aggregateMetricModel->getAggregateMetricsForSubmission($submission2Dao);
        $this->assertEquals(1, count($aggregateMetricDaos));
        $this->assertEquals($aggregateMetricDaos[0]->getValue(), 14.0);

        /** @var AggregateMetricSpecDao $cachedAMSDao */
        $cachedAMSDao = $aggregateMetricSpecModel->load($validAMSDao->getAggregateMetricSpecId());

        // Delete the created spec, which should cascade to the metrics, the notifications, and the notification users.
        $aggregateMetricSpecModel->delete($validAMSDao);

        $aggregateMetricDaos = $aggregateMetricModel->getAggregateMetricsForSubmission($submission1Dao);
        $this->assertEquals(0, count($aggregateMetricDaos));
        $aggregateMetricDaos = $aggregateMetricModel->getAggregateMetricsForSubmission($submission2Dao);
        $this->assertEquals(0, count($aggregateMetricDaos));

        /** @var AggregateMetricSpecDao $loadedAMSDao */
        $loadedAMSDao = $aggregateMetricSpecModel->load($cachedAMSDao->getAggregateMetricSpecId());
        $this->assertFalse($loadedAMSDao);

        // There shouldn't be any linked users.
        $this->assertEquals(0, count($aggregateMetricNotificationModel->getAllNotifiedUsers($notification1Dao)));
        // Reloading the notification DAO should produce false.
        $notification1Dao = $aggregateMetricNotificationModel->load($notification1Dao->getAggregateMetricNotificationId());
        $this->assertFalse($notification1Dao);
    }
}
