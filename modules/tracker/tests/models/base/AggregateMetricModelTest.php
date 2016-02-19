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

/** Test the AggregateMetric models. */
class Tracker_AggregateMetricModelTest extends DatabaseTestCase
{
    /** Set up tests. */
    public function setUp()
    {
        $this->setupDatabase(array('default')); // core dataset
        $this->setupDatabase(array('aggregateMetric'), 'tracker'); // module dataset
        $this->enabledModules = array('tracker');
        parent::setUp();
    }

    /** test AggregateMetricModel getAggregateMetricInputValuesForSubmission function */
    public function testGetAggregateMetricInputValuesForSubmission()
    {
        /** @var Tracker_ProducerModel $producerModel */
        $producerModel = MidasLoader::loadModel('Producer', 'tracker');
        /** @var Tracker_SubmissionModel $submissionModel */
        $submissionModel = MidasLoader::loadModel('Submission', 'tracker');
        /** @var AggregateMetricSpecModel $aggregateMetricSpecModel */
        $aggregateMetricSpecModel = MidasLoader::loadModel('AggregateMetricSpec', 'tracker');
        /** @var AggregateMetricModel $aggregateMetricModel */
        $aggregateMetricModel = MidasLoader::loadModel('AggregateMetric', 'tracker');

        /** @var Tracker_ProducerDao $producer100Dao */
        $producer100Dao = $producerModel->load(100);
        /** @var Tracker_SubmissionDao $submission1Dao */
        $submission1Dao = $submissionModel->load(1);
        /** @var Tracker_SubmissionDao $submission2Dao */
        $submission2Dao = $submissionModel->load(2);

        /** @var Tracker_AggregateMetricSpecDao $greedyError95thPercentileAMSDao */
        $greedyError95thPercentileAMSDao = $aggregateMetricSpecModel->load(1);
        /** @var Tracker_AggregateMetricSpecDao $greedyError55thPercentileAMSDao */
        $greedyError55thPercentileAMSDao = $aggregateMetricSpecModel->load(2);

        $values = $aggregateMetricModel->getAggregateMetricInputValuesForSubmission($greedyError95thPercentileAMSDao, $submission1Dao);
        $testValues = range(1, 20);
        $this->assertEquals(count($testValues), count($values));
        foreach ($testValues as $ind => $value) {
            $this->assertEquals($value, $values[$ind]);
        }

        $values = $aggregateMetricModel->getAggregateMetricInputValuesForSubmission($greedyError55thPercentileAMSDao, $submission1Dao);
        // Values should be the same, only the metric is different.
        $this->assertEquals(count($testValues), count($values));
        foreach ($testValues as $ind => $value) {
            $this->assertEquals($value, $values[$ind]);
        }

        $values = $aggregateMetricModel->getAggregateMetricInputValuesForSubmission($greedyError95thPercentileAMSDao, $submission2Dao);
        $testValues = range(2, 40, 2);
        $this->assertEquals(count($testValues), count($values));
        foreach ($testValues as $ind => $value) {
            $this->assertEquals($value, $values[$ind]);
        }

        $values = $aggregateMetricModel->getAggregateMetricInputValuesForSubmission($greedyError55thPercentileAMSDao, $submission2Dao);
        // Values should be the same, only the metric is different.
        $this->assertEquals(count($testValues), count($values));
        foreach ($testValues as $ind => $value) {
            $this->assertEquals($value, $values[$ind]);
        }

        /** @var Tracker_AggregateMetricSpecDao $optimalError95thPercentileAMSDao */
        $optimalError95thPercentileAMSDao = $aggregateMetricSpecModel->load(3);
        /** @var Tracker_AggregateMetricSpecDao $optimalError55thPercentileAMSDao */
        $optimalError55thPercentileAMSDao = $aggregateMetricSpecModel->load(4);

        $values = $aggregateMetricModel->getAggregateMetricInputValuesForSubmission($optimalError95thPercentileAMSDao, $submission1Dao);
        $testValues = range(26, 45);
        $this->assertEquals(count($testValues), count($values));
        foreach ($testValues as $ind => $value) {
            $this->assertEquals($value, $values[$ind]);
        }

        $values = $aggregateMetricModel->getAggregateMetricInputValuesForSubmission($optimalError55thPercentileAMSDao, $submission1Dao);
        // Values should be the same, only the metric is different.
        $this->assertEquals(count($testValues), count($values));
        foreach ($testValues as $ind => $value) {
            $this->assertEquals($value, $values[$ind]);
        }

        $values = $aggregateMetricModel->getAggregateMetricInputValuesForSubmission($optimalError95thPercentileAMSDao, $submission2Dao);
        $testValues = range(36, 55);
        $this->assertEquals(count($testValues), count($values));
        foreach ($testValues as $ind => $value) {
            $this->assertEquals($value, $values[$ind]);
        }

        $values = $aggregateMetricModel->getAggregateMetricInputValuesForSubmission($optimalError55thPercentileAMSDao, $submission2Dao);
        // Values should be the same, only the metric is different.
        $this->assertEquals(count($testValues), count($values));
        foreach ($testValues as $ind => $value) {
            $this->assertEquals($value, $values[$ind]);
        }

        // Test combinations of null inputs.
        $values = $aggregateMetricModel->getAggregateMetricInputValuesForSubmission(null, $submission1Dao);
        $this->assertFalse($values);
        $values = $aggregateMetricModel->getAggregateMetricInputValuesForSubmission($optimalError55thPercentileAMSDao, null);
        $this->assertFalse($values);
        $values = $aggregateMetricModel->getAggregateMetricInputValuesForSubmission(null, null);
        $this->assertFalse($values);
    }

    /** test AggregateMetricModel computeAggregateMetricForSubmission function */
    public function testComputeAggregateMetricForSubmission()
    {
        /** @var Tracker_ProducerModel $producerModel */
        $producerModel = MidasLoader::loadModel('Producer', 'tracker');
        /** @var Tracker_SubmissionModel $submissionModel */
        $submissionModel = MidasLoader::loadModel('Submission', 'tracker');
        /** @var AggregateMetricSpecModel $aggregateMetricSpecModel */
        $aggregateMetricSpecModel = MidasLoader::loadModel('AggregateMetricSpec', 'tracker');
        /** @var AggregateMetricModel $aggregateMetricModel */
        $aggregateMetricModel = MidasLoader::loadModel('AggregateMetric', 'tracker');

        /** @var Tracker_ProducerDao $producer100Dao */
        $producer100Dao = $producerModel->load(100);
        /** @var Tracker_SubmissionDao $submission1Dao */
        $submission1Dao = $submissionModel->load(1);
        /** @var Tracker_SubmissionDao $submission2Dao */
        $submission2Dao = $submissionModel->load(2);

        /** @var Tracker_AggregateMetricSpecDao $greedyError95thPercentileAMSDao */
        $greedyError95thPercentileAMSDao = $aggregateMetricSpecModel->load(1);
        /** @var Tracker_AggregateMetricSpecDao $greedyError55thPercentileAMSDao */
        $greedyError55thPercentileAMSDao = $aggregateMetricSpecModel->load(2);

        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($greedyError95thPercentileAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 19.0);

        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($greedyError95thPercentileAMSDao, $submission2Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 38.0);

        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($greedyError55thPercentileAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 11.0);

        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($greedyError55thPercentileAMSDao, $submission2Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 22.0);

        /** @var Tracker_AggregateMetricSpecDao $optimalError95thPercentileAMSDao */
        $optimalError95thPercentileAMSDao = $aggregateMetricSpecModel->load(3);
        /** @var Tracker_AggregateMetricSpecDao $optimalError55thPercentileAMSDao */
        $optimalError55thPercentileAMSDao = $aggregateMetricSpecModel->load(4);

        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($optimalError95thPercentileAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 44.0);

        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($optimalError95thPercentileAMSDao, $submission2Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 54.0);

        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($optimalError55thPercentileAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 36.0);

        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($optimalError55thPercentileAMSDao, $submission2Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 46.0);

        /** @var Tracker_AggregateMetricSpecDao $greedyDistance55thPercentileAMSDao */
        $greedyDistance55thPercentileAMSDao = $aggregateMetricSpecModel->load(5);

        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($greedyDistance55thPercentileAMSDao, $submission1Dao);
        // This has no trends that are key metrics.
        $this->assertEquals($aggregateMetricDao, false);

        /** @var Tracker_AggregateMetricSpecDao $optimalDistance55thPercentileAMSDao */
        $optimalDistance55thPercentileAMSDao = $aggregateMetricSpecModel->load(6);

        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($optimalDistance55thPercentileAMSDao, $submission1Dao);
        // Trends have no scalars.
        $this->assertEquals($aggregateMetricDao, false);

        // AMS that doesn't match any trends.
        $name = '95th Percentile Noop distance ';
        $schema = "percentile('Noop distance', 95)";
        /** @var Tracker_AggregateMetricSpecDao $noopDistance95thPercentileAMSDao */
        $noopDistance95thPercentileAMSDao = $aggregateMetricSpecModel->createAggregateMetricSpec($producer100Dao, $name, $schema);
        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($noopDistance95thPercentileAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao, false);

        // AMS that doesn't match any branches.
        /** @var Tracker_AggregateMetricSpecDao $noopDistance95thPercentileTestAMSDao */
        $branch = 'test';
        $noopDistance95thPercentileTestAMSDao = $aggregateMetricSpecModel->createAggregateMetricSpec($producer100Dao, $name, $schema, $branch);
        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($noopDistance95thPercentileTestAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao, false);

        // AMS with missing percentile param.
        /** @var Tracker_AggregateMetricSpecDao $greedyErrorMissingPercentileAMSDao */
        $name = 'Percentile Greedy error';
        $schema = "percentile('Greedy error')";
        $greedyErrorMissingPercentileAMSDao = $aggregateMetricSpecModel->createAggregateMetricSpec($producer100Dao, $name, $schema);
        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($greedyErrorMissingPercentileAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao, false);

        // AMS with percentile param that won't resolve to an int.
        /** @var Tracker_AggregateMetricSpecDao $greedyError9333PercentileAMSDao */
        $name = '93.33 Percentile Greedy error';
        $schema = "percentile('Greedy error', 93.33)";
        $greedyError9333PercentileAMSDao = $aggregateMetricSpecModel->createAggregateMetricSpec($producer100Dao, $name, $schema);
        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($greedyError9333PercentileAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 19.0);

        // Test combinations of null inputs.
        $nullAMSAggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission(null, $submission1Dao);
        $this->assertFalse($nullAMSAggregateMetricDao);
        $nullSubmissionAggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($greedyError9333PercentileAMSDao, null);
        $this->assertFalse($nullSubmissionAggregateMetricDao);
        $nullBothAggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission(null, null);
        $this->assertFalse($nullBothAggregateMetricDao);
    }

    /** test AggregateMetricModel getAggregateMetricsForSubmission function */
    public function testGetAggregateMetricsForSubmission()
    {
        /** @var Tracker_ProducerModel $producerModel */
        $producerModel = MidasLoader::loadModel('Producer', 'tracker');
        /** @var Tracker_SubmissionModel $submissionModel */
        $submissionModel = MidasLoader::loadModel('Submission', 'tracker');
        /** @var AggregateMetricModel $aggregateMetricModel */
        $aggregateMetricModel = MidasLoader::loadModel('AggregateMetric', 'tracker');
        /** @var AggregateMetricSpecModel $aggregateMetricSpecModel */
        $aggregateMetricSpecModel = MidasLoader::loadModel('AggregateMetricSpec', 'tracker');

        /** @var Tracker_ProducerDao $producer100Dao */
        $producer100Dao = $producerModel->load(100);
        /** @var Tracker_SubmissionDao $submission1Dao */
        $submission1Dao = $submissionModel->load(1);
        /** @var Tracker_SubmissionDao $submission2Dao */
        $submission2Dao = $submissionModel->load(2);

        /** @var Tracker_AggregateMetricSpecDao $greedyError95thPercentileAMSDao */
        $greedyError95thPercentileAMSDao = $aggregateMetricSpecModel->load(1);
        /** @var Tracker_AggregateMetricSpecDao $greedyError55thPercentileAMSDao */
        $greedyError55thPercentileAMSDao = $aggregateMetricSpecModel->load(2);
        /** @var Tracker_AggregateMetricSpecDao $optimalError95thPercentileAMSDao */
        $optimalError95thPercentileAMSDao = $aggregateMetricSpecModel->load(3);
        /** @var Tracker_AggregateMetricSpecDao $optimalError55thPercentileAMSDao */
        $optimalError55thPercentileAMSDao = $aggregateMetricSpecModel->load(4);

        $submission1AggregateMetricIds = array();
        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($greedyError95thPercentileAMSDao, $submission1Dao);
        $submission1AggregateMetricIds[$aggregateMetricDao->getAggregateMetricId()] = false;
        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($greedyError55thPercentileAMSDao, $submission1Dao);
        $submission1AggregateMetricIds[$aggregateMetricDao->getAggregateMetricId()] = false;
        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($optimalError95thPercentileAMSDao, $submission1Dao);
        $submission1AggregateMetricIds[$aggregateMetricDao->getAggregateMetricId()] = false;
        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($optimalError55thPercentileAMSDao, $submission1Dao);
        $submission1AggregateMetricIds[$aggregateMetricDao->getAggregateMetricId()] = false;

        $submission1AggregateMetrics = $aggregateMetricModel->getAggregateMetricsForSubmission($submission1Dao);
        /** @var Tracker_AggregateMetricDao $submission1AggregateMetricDao */
        foreach ($submission1AggregateMetrics as $submission1AggregateMetricDao) {
            if (array_key_exists($submission1AggregateMetricDao->getAggregateMetricId(), $submission1AggregateMetricIds)) {
                $submission1AggregateMetricIds[$submission1AggregateMetricDao->getAggregateMetricId()] = true;
            }
        }
        /** @var string $submission1AggregateMetricId */
        /** @var bool $found */
        foreach ($submission1AggregateMetricIds as $submission1AggregateMetricId => $found) {
            $this->assertTrue($found);
        }

        $submission2AggregateMetricIds = array();
        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($greedyError95thPercentileAMSDao, $submission2Dao);
        $submission2AggregateMetricIds[$aggregateMetricDao->getAggregateMetricId()] = false;
        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($greedyError55thPercentileAMSDao, $submission2Dao);
        $submission2AggregateMetricIds[$aggregateMetricDao->getAggregateMetricId()] = false;
        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($optimalError95thPercentileAMSDao, $submission2Dao);
        $submission2AggregateMetricIds[$aggregateMetricDao->getAggregateMetricId()] = false;
        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($optimalError55thPercentileAMSDao, $submission2Dao);
        $submission2AggregateMetricIds[$aggregateMetricDao->getAggregateMetricId()] = false;

        $submission2AggregateMetrics = $aggregateMetricModel->getAggregateMetricsForSubmission($submission2Dao);
        /** @var Tracker_AggregateMetricDao $submission2AggregateMetricDao */
        foreach ($submission2AggregateMetrics as $submission2AggregateMetricDao) {
            if (array_key_exists($submission2AggregateMetricDao->getAggregateMetricId(), $submission2AggregateMetricIds)) {
                $submission2AggregateMetricIds[$submission2AggregateMetricDao->getAggregateMetricId()] = true;
            }
        }
        /** @var string $submission2AggregateMetricId */
        /** @var bool $found */
        foreach ($submission2AggregateMetricIds as $submission2AggregateMetricId => $found) {
            $this->assertTrue($found);
        }

        // Test null input.
        $nullSubmissionAggregateMetrics = $aggregateMetricModel->getAggregateMetricsForSubmission(null);
        $this->assertFalse($nullSubmissionAggregateMetrics);
    }
}
