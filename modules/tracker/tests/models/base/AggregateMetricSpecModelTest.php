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
    /** Set up tests. */
    public function setUp()
    {
        $this->setupDatabase(array('default')); // core dataset
        $this->setupDatabase(array('aggregateMetric'), 'tracker'); // module dataset
        $this->enabledModules = array('tracker');
        $db = Zend_Registry::get('dbAdapter');
        $configDatabase = Zend_Registry::get('configDatabase');
        if ($configDatabase->database->adapter == 'PDO_PGSQL') {
            $db->query("SELECT setval('tracker_aggregate_metric_spec_aggregate_metric_spec_id_seq', (SELECT MAX(aggregate_metric_spec_id) FROM tracker_aggregate_metric_spec)+1);");
            $db->query("SELECT setval('tracker_aggregate_metric_aggregate_metric_id_seq', (SELECT MAX(aggregate_metric_id) FROM tracker_aggregate_metric)+1);");
        }
        parent::setUp();
    }

    /** test AggregateMetricSpecModel createAggregateMetricSpec function */
    public function testCreateAggregateMetricSpec()
    {
        /** @var AggregateMetricSpecModel $aggregateMetricSpecModel */
        $aggregateMetricSpecModel = MidasLoader::loadModel('AggregateMetricSpec', 'tracker');

        $name = '95th Percentile Greedy distance ';
        $schema = "percentile('Greedy distance', 95)";

        // Pass a null producer.
        $emptyProducerAMSDao = $aggregateMetricSpecModel->createAggregateMetricSpec(null, $name, $schema);
        $this->assertFalse($emptyProducerAMSDao);

        /** @var Tracker_ProducerModel $producerModel */
        $producerModel = MidasLoader::loadModel('Producer', 'tracker');
        /** @var Tracker_ProducerDao $producer100Dao */
        $producer100Dao = $producerModel->load(100);

        $validAMSDao = $aggregateMetricSpecModel->createAggregateMetricSpec($producer100Dao, $name, $schema);
        $this->assertEquals($validAMSDao->getName(), $name);
        $this->assertEquals($validAMSDao->getSchema(), $schema);
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
}
