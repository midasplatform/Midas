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
        $db = Zend_Registry::get('dbAdapter');
        $configDatabase = Zend_Registry::get('configDatabase');
        if ($configDatabase->database->adapter == 'PDO_PGSQL') {
            $db->query("SELECT setval('tracker_aggregate_metric_spec_aggregate_metric_spec_id_seq', (SELECT MAX(aggregate_metric_spec_id) FROM tracker_aggregate_metric_spec)+1);");
            $db->query("SELECT setval('tracker_aggregate_metric_aggregate_metric_id_seq', (SELECT MAX(aggregate_metric_id) FROM tracker_aggregate_metric)+1);");
            $db->query("SELECT setval('tracker_trend_trend_id_seq', (SELECT MAX(trend_id) FROM tracker_trend)+1);");
            $db->query("SELECT setval('tracker_scalar_scalar_id_seq', (SELECT MAX(scalar_id) FROM tracker_scalar)+1);");
        }
        parent::setUp();
    }

    /** createAdditionalGreedyErrorSubmission1Scalars testing utility function. */
    protected function createAdditionalGreedyErrorSubmission1Scalars()
    {
        /** @var Tracker_TrendModel $trendModel */
        $trendModel = MidasLoader::loadModel('Trend', 'tracker');
        /** @var Tracker_ScalarModel $scalarModel */
        $scalarModel = MidasLoader::loadModel('Scalar', 'tracker');

        $extraTrends = array();
        /** @var int $i */
        for ($i = 0; $i < 4; ++$i) {
            /** @var Tracker_TrendDao $trendDao */
            $trendDao = $trendModel->createIfNeeded(100, 'Greedy error', 1000, 2000 + $i, 2000);
            $trendDao->setKeyMetric(1);
            $trendModel->save($trendDao);
            /** @var Tracker_ScalarDao $scalarDao */
            $scalarDao = MidasLoader::newDao('ScalarDao', 'tracker');
            $scalarDao->setSubmissionId(1);
            $scalarDao->setTrendId($trendDao->getKey());
            $scalarDao->setSubmitTime(date('Y-m-d', time()));
            $scalarDao->setProducerRevision(1);
            $scalarDao->setValue(21.0 + $i);
            $scalarDao->setUserId(1);
            $scalarDao->setOfficial((int) true);
            $scalarDao->setBuildResultsUrl('build.results.url');
            $scalarDao->setBranch('master');
            $scalarModel->save($scalarDao);
            $extraTrends[] = $trendDao;
        }

        return $extraTrends;
    }

    /** deleteExtraTrends testing utility function. */
    protected function deleteExtraTrends($extraTrends)
    {
        /** @var Tracker_TrendModel $trendModel */
        $trendModel = MidasLoader::loadModel('Trend', 'tracker');
        /** @var Tracker_TrendDao $trendDao */
        foreach ($extraTrends as $trendDao) {
            $trendModel->delete($trendDao);
        }
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

        // Add 4 trends with a scalar each and test again.
        $extraTrends = $this->createAdditionalGreedyErrorSubmission1Scalars();

        $values = $aggregateMetricModel->getAggregateMetricInputValuesForSubmission($greedyError95thPercentileAMSDao, $submission1Dao);
        $testValues = range(1, 24);
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

        $this->deleteExtraTrends($extraTrends);

        // Retest after deletion.
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

        // Keep the counts of saved metrics to ensure no new ones are saved.
        $submission1Metrics = $aggregateMetricModel->getAggregateMetricsForSubmission($submission1Dao);
        $submission1MetricsInitialCount = count($submission1Metrics);
        $submission2Metrics = $aggregateMetricModel->getAggregateMetricsForSubmission($submission2Dao);
        $submission2MetricsInitialCount = count($submission2Metrics);

        /** @var Tracker_AggregateMetricSpecDao $greedyError95thPercentileAMSDao */
        $greedyError95thPercentileAMSDao = $aggregateMetricSpecModel->load(1);
        /** @var Tracker_AggregateMetricSpecDao $greedyError55thPercentileAMSDao */
        $greedyError55thPercentileAMSDao = $aggregateMetricSpecModel->load(2);
        /** @var Tracker_AggregateMetricSpecDao $greedyError0thPercentileAMSDao */
        $greedyError0thPercentileAMSDao = $aggregateMetricSpecModel->load(7);

        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($greedyError0thPercentileAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 1.0);

        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($greedyError0thPercentileAMSDao, $submission2Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 2.0);

        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($greedyError95thPercentileAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 19.0);

        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($greedyError95thPercentileAMSDao, $submission2Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 38.0);

        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($greedyError55thPercentileAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 11.0);

        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($greedyError55thPercentileAMSDao, $submission2Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 22.0);

        // Add 4 trends with a scalar each and test again.
        $extraTrends = $this->createAdditionalGreedyErrorSubmission1Scalars();

        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($greedyError0thPercentileAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 1.0);

        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($greedyError0thPercentileAMSDao, $submission2Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 2.0);

        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($greedyError95thPercentileAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 23.0);

        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($greedyError55thPercentileAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 13.0);

        $this->deleteExtraTrends($extraTrends);

        // Retest after deletion.
        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($greedyError0thPercentileAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 1.0);

        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($greedyError0thPercentileAMSDao, $submission2Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 2.0);

        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($greedyError95thPercentileAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 19.0);

        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($greedyError55thPercentileAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 11.0);

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
        // This has no trends that are key metrics.
        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($greedyDistance55thPercentileAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao, false);

        /** @var Tracker_AggregateMetricSpecDao $optimalDistance55thPercentileAMSDao */
        $optimalDistance55thPercentileAMSDao = $aggregateMetricSpecModel->load(6);
        // These trends have no scalars.
        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($optimalDistance55thPercentileAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao, false);

        // AMSes that match branch 'test', with only one scalar.

        /** @var Tracker_SubmissionDao $submission8Dao */
        $submission8Dao = $submissionModel->load(8);

        /** @var Tracker_AggregateMetricSpecDao $greedyErrorTest0thPercentileAMSDao */
        $greedyErrorTest0thPercentileAMSDao = $aggregateMetricSpecModel->load(8);
        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($greedyErrorTest0thPercentileAMSDao, $submission8Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 654.0);

        /** @var Tracker_AggregateMetricSpecDao $greedyErrorTest55thPercentileAMSDao */
        $greedyErrorTest55thPercentileAMSDao = $aggregateMetricSpecModel->load(9);
        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($greedyErrorTest55thPercentileAMSDao, $submission8Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 654.0);

        /** @var Tracker_AggregateMetricSpecDao $greedyErrorTest99thPercentileAMSDao */
        $greedyErrorTest99thPercentileAMSDao = $aggregateMetricSpecModel->load(10);
        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($greedyErrorTest99thPercentileAMSDao, $submission8Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 654.0);

        // AMS that doesn't match any trends.
        $name = '95th Percentile Noop distance ';
        $spec = "percentile('Noop distance', 95)";
        /** @var Tracker_AggregateMetricSpecDao $noopDistance95thPercentileAMSDao */
        $noopDistance95thPercentileAMSDao = $aggregateMetricSpecModel->createAggregateMetricSpec($producer100Dao, $name, $spec);
        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($noopDistance95thPercentileAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao, false);

        // AMS that doesn't match any branches.
        /** @var Tracker_AggregateMetricSpecDao $noopDistance95thPercentileTestAMSDao */
        $branch = 'test';
        $noopDistance95thPercentileTestAMSDao = $aggregateMetricSpecModel->createAggregateMetricSpec($producer100Dao, $name, $spec, $branch);
        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($noopDistance95thPercentileTestAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao, false);

        // AMS with missing percentile param.
        /** @var Tracker_AggregateMetricSpecDao $greedyErrorMissingPercentileAMSDao */
        $name = 'Percentile Greedy error';
        $spec = "percentile('Greedy error')";
        $greedyErrorMissingPercentileAMSDao = $aggregateMetricSpecModel->createAggregateMetricSpec($producer100Dao, $name, $spec);
        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($greedyErrorMissingPercentileAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao, false);

        // AMS with percentile param that won't resolve to an int.
        /** @var Tracker_AggregateMetricSpecDao $greedyError9333PercentileAMSDao */
        $name = '93.33 Percentile Greedy error';
        $spec = "percentile('Greedy error', 93.33)";
        $greedyError9333PercentileAMSDao = $aggregateMetricSpecModel->createAggregateMetricSpec($producer100Dao, $name, $spec);
        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($greedyError9333PercentileAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 19.0);

        // Test combinations of null inputs.
        $nullAMSAggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission(null, $submission1Dao);
        $this->assertFalse($nullAMSAggregateMetricDao);
        $nullSubmissionAggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($greedyError9333PercentileAMSDao, null);
        $this->assertFalse($nullSubmissionAggregateMetricDao);
        $nullBothAggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission(null, null);
        $this->assertFalse($nullBothAggregateMetricDao);

        // Finally ensure that no new aggregate metrics have been saved.
        $submission1Metrics = $aggregateMetricModel->getAggregateMetricsForSubmission($submission1Dao);
        $submission1MetricsFinalCount = count($submission1Metrics);
        $submission2Metrics = $aggregateMetricModel->getAggregateMetricsForSubmission($submission2Dao);
        $submission2MetricsFinalCount = count($submission2Metrics);

        $this->assertEquals($submission1MetricsInitialCount, $submission1MetricsFinalCount);
        $this->assertEquals($submission2MetricsInitialCount, $submission2MetricsFinalCount);
    }

    /** test AggregateMetricModel updateAggregateMetricForSubmission function */
    public function testUpdateAggregateMetricForSubmission()
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

        $aggregateMetricDao = $aggregateMetricModel->updateAggregateMetricForSubmission($greedyError95thPercentileAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 19.0);

        $aggregateMetricDao = $aggregateMetricModel->updateAggregateMetricForSubmission($greedyError95thPercentileAMSDao, $submission2Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 38.0);

        $aggregateMetricDao = $aggregateMetricModel->updateAggregateMetricForSubmission($greedyError55thPercentileAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 11.0);

        $aggregateMetricDao = $aggregateMetricModel->updateAggregateMetricForSubmission($greedyError55thPercentileAMSDao, $submission2Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 22.0);

        // Add 4 trends with a scalar each and test again.
        $extraTrends = $this->createAdditionalGreedyErrorSubmission1Scalars();

        $aggregateMetricDao = $aggregateMetricModel->updateAggregateMetricForSubmission($greedyError95thPercentileAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 23.0);

        $aggregateMetricDao = $aggregateMetricModel->updateAggregateMetricForSubmission($greedyError55thPercentileAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 13.0);

        $this->deleteExtraTrends($extraTrends);

        // Retest after deletion.
        $aggregateMetricDao = $aggregateMetricModel->updateAggregateMetricForSubmission($greedyError95thPercentileAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 19.0);

        $aggregateMetricDao = $aggregateMetricModel->updateAggregateMetricForSubmission($greedyError55thPercentileAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 11.0);

        /** @var Tracker_AggregateMetricSpecDao $optimalError95thPercentileAMSDao */
        $optimalError95thPercentileAMSDao = $aggregateMetricSpecModel->load(3);
        /** @var Tracker_AggregateMetricSpecDao $optimalError55thPercentileAMSDao */
        $optimalError55thPercentileAMSDao = $aggregateMetricSpecModel->load(4);

        $aggregateMetricDao = $aggregateMetricModel->updateAggregateMetricForSubmission($optimalError95thPercentileAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 44.0);

        $aggregateMetricDao = $aggregateMetricModel->updateAggregateMetricForSubmission($optimalError95thPercentileAMSDao, $submission2Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 54.0);

        $aggregateMetricDao = $aggregateMetricModel->updateAggregateMetricForSubmission($optimalError55thPercentileAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 36.0);

        $aggregateMetricDao = $aggregateMetricModel->updateAggregateMetricForSubmission($optimalError55thPercentileAMSDao, $submission2Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 46.0);

        /** @var Tracker_AggregateMetricSpecDao $greedyDistance55thPercentileAMSDao */
        $greedyDistance55thPercentileAMSDao = $aggregateMetricSpecModel->load(5);

        $aggregateMetricDao = $aggregateMetricModel->updateAggregateMetricForSubmission($greedyDistance55thPercentileAMSDao, $submission1Dao);
        // This has no trends that are key metrics.
        $this->assertEquals($aggregateMetricDao, false);

        /** @var Tracker_AggregateMetricSpecDao $optimalDistance55thPercentileAMSDao */
        $optimalDistance55thPercentileAMSDao = $aggregateMetricSpecModel->load(6);

        $aggregateMetricDao = $aggregateMetricModel->updateAggregateMetricForSubmission($optimalDistance55thPercentileAMSDao, $submission1Dao);
        // Trends have no scalars.
        $this->assertEquals($aggregateMetricDao, false);

        // AMS that doesn't match any trends.
        $name = '95th Percentile Noop distance ';
        $spec = "percentile('Noop distance', 95)";
        /** @var Tracker_AggregateMetricSpecDao $noopDistance95thPercentileAMSDao */
        $noopDistance95thPercentileAMSDao = $aggregateMetricSpecModel->createAggregateMetricSpec($producer100Dao, $name, $spec);
        $aggregateMetricDao = $aggregateMetricModel->updateAggregateMetricForSubmission($noopDistance95thPercentileAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao, false);

        // AMS that doesn't match any branches.
        /** @var Tracker_AggregateMetricSpecDao $noopDistance95thPercentileTestAMSDao */
        $branch = 'test';
        $noopDistance95thPercentileTestAMSDao = $aggregateMetricSpecModel->createAggregateMetricSpec($producer100Dao, $name, $spec, $branch);
        $aggregateMetricDao = $aggregateMetricModel->updateAggregateMetricForSubmission($noopDistance95thPercentileTestAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao, false);

        // AMS with missing percentile param.
        /** @var Tracker_AggregateMetricSpecDao $greedyErrorMissingPercentileAMSDao */
        $name = 'Percentile Greedy error';
        $spec = "percentile('Greedy error')";
        $greedyErrorMissingPercentileAMSDao = $aggregateMetricSpecModel->createAggregateMetricSpec($producer100Dao, $name, $spec);
        $aggregateMetricDao = $aggregateMetricModel->updateAggregateMetricForSubmission($greedyErrorMissingPercentileAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao, false);

        // AMS with percentile param that won't resolve to an int.
        /** @var Tracker_AggregateMetricSpecDao $greedyError9333PercentileAMSDao */
        $name = '93.33 Percentile Greedy error';
        $spec = "percentile('Greedy error', 93.33)";
        $greedyError9333PercentileAMSDao = $aggregateMetricSpecModel->createAggregateMetricSpec($producer100Dao, $name, $spec);
        $aggregateMetricDao = $aggregateMetricModel->updateAggregateMetricForSubmission($greedyError9333PercentileAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 19.0);

        // Test combinations of null inputs.
        $nullAMSAggregateMetricDao = $aggregateMetricModel->updateAggregateMetricForSubmission(null, $submission1Dao);
        $this->assertFalse($nullAMSAggregateMetricDao);
        $nullSubmissionAggregateMetricDao = $aggregateMetricModel->updateAggregateMetricForSubmission($greedyError9333PercentileAMSDao, null);
        $this->assertFalse($nullSubmissionAggregateMetricDao);
        $nullBothAggregateMetricDao = $aggregateMetricModel->updateAggregateMetricForSubmission(null, null);
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
        $aggregateMetricDao = $aggregateMetricModel->updateAggregateMetricForSubmission($greedyError95thPercentileAMSDao, $submission1Dao);
        $submission1AggregateMetricIds[$aggregateMetricDao->getAggregateMetricId()] = false;
        $aggregateMetricDao = $aggregateMetricModel->updateAggregateMetricForSubmission($greedyError55thPercentileAMSDao, $submission1Dao);
        $submission1AggregateMetricIds[$aggregateMetricDao->getAggregateMetricId()] = false;
        $aggregateMetricDao = $aggregateMetricModel->updateAggregateMetricForSubmission($optimalError95thPercentileAMSDao, $submission1Dao);
        $submission1AggregateMetricIds[$aggregateMetricDao->getAggregateMetricId()] = false;
        $aggregateMetricDao = $aggregateMetricModel->updateAggregateMetricForSubmission($optimalError55thPercentileAMSDao, $submission1Dao);
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
        $aggregateMetricDao = $aggregateMetricModel->updateAggregateMetricForSubmission($greedyError95thPercentileAMSDao, $submission2Dao);
        $submission2AggregateMetricIds[$aggregateMetricDao->getAggregateMetricId()] = false;
        $aggregateMetricDao = $aggregateMetricModel->updateAggregateMetricForSubmission($greedyError55thPercentileAMSDao, $submission2Dao);
        $submission2AggregateMetricIds[$aggregateMetricDao->getAggregateMetricId()] = false;
        $aggregateMetricDao = $aggregateMetricModel->updateAggregateMetricForSubmission($optimalError95thPercentileAMSDao, $submission2Dao);
        $submission2AggregateMetricIds[$aggregateMetricDao->getAggregateMetricId()] = false;
        $aggregateMetricDao = $aggregateMetricModel->updateAggregateMetricForSubmission($optimalError55thPercentileAMSDao, $submission2Dao);
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

    /** test AggregateMetricModel computeAggregateMetricsForSubmission function */
    public function testComputeAggregateMetricsForSubmission()
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

        // Keep the counts of saved metrics to ensure no new ones are saved.
        $submission1Metrics = $aggregateMetricModel->updateAggregateMetricsForSubmission($submission1Dao);
        $submission1MetricsInitialCount = count($submission1Metrics);
        $submission2Metrics = $aggregateMetricModel->updateAggregateMetricsForSubmission($submission2Dao);
        $submission2MetricsInitialCount = count($submission2Metrics);

        // Create a new AMS, with a value of 5.0 for submission 1 and 10.0 for submission 2.
        /** @var Tracker_AggregateMetricSpecDao $greedyError25thPercentileAMSDao */
        $name = '25th Percentile Greedy error';
        $spec = "percentile('Greedy error', 25)";
        $greedyError25thPercentileAMSDao = $aggregateMetricSpecModel->createAggregateMetricSpec($producer100Dao, $name, $spec);

        $submission1AggregateMetricDaos = $aggregateMetricModel->computeAggregateMetricsForSubmission($submission1Dao);
        $submission1MetricValues = array(
            array(19.0 => false),
            array(11.0 => false),
            array(44.0 => false),
            array(36.0 => false),
            array(5.0 => false),
            array(1.0 => false),
        );
        $this->assertEquals(count($submission1AggregateMetricDaos), count($submission1MetricValues));

        /** @var Tracker_AggregateMetricDao $submission1AggregateMetricDao */
        foreach ($submission1AggregateMetricDaos as $submission1AggregateMetricDao) {
            /** @var int $index */
            /** @var array $submission1MetricValue */
            foreach ($submission1MetricValues as $index => $submission1MetricValue) {
                if ($submission1AggregateMetricDao->getValue() == key($submission1MetricValue)) {
                    $submission1MetricValues[$index] = array(key($submission1MetricValue) => true);
                }
            }
        }
        /** @var int $index */
        /** @var float $submission1MetricValue */
        foreach ($submission1MetricValues as $index => $submission1MetricValue) {
            $this->assertTrue($submission1MetricValue[key($submission1MetricValue)]);
        }

        // Add 4 trends with a scalar each and test again.
        $extraTrends = $this->createAdditionalGreedyErrorSubmission1Scalars();

        $submission1AggregateMetricDaos = $aggregateMetricModel->computeAggregateMetricsForSubmission($submission1Dao);
        $submission1MetricValues = array(
            array(23.0 => false),
            array(13.0 => false),
            array(44.0 => false),
            array(36.0 => false),
            array(6.0 => false),
            array(1.0 => false),
        );
        $this->assertEquals(count($submission1AggregateMetricDaos), count($submission1MetricValues));

        /** @var Tracker_AggregateMetricDao $submission1AggregateMetricDao */
        foreach ($submission1AggregateMetricDaos as $submission1AggregateMetricDao) {
            /** @var int $index */
            /** @var array $submission1MetricValue */
            foreach ($submission1MetricValues as $index => $submission1MetricValue) {
                if ($submission1AggregateMetricDao->getValue() == key($submission1MetricValue)) {
                    $submission1MetricValues[$index] = array(key($submission1MetricValue) => true);
                }
            }
        }
        /** @var int $index */
        /** @var float $submission1MetricValue */
        foreach ($submission1MetricValues as $index => $submission1MetricValue) {
            $this->assertTrue($submission1MetricValue[key($submission1MetricValue)]);
        }

        $this->deleteExtraTrends($extraTrends);

        // Retest after deletion.
        $submission1AggregateMetricDaos = $aggregateMetricModel->computeAggregateMetricsForSubmission($submission1Dao);
        $submission1MetricValues = array(
            array(19.0 => false),
            array(11.0 => false),
            array(44.0 => false),
            array(36.0 => false),
            array(5.0 => false),
            array(1.0 => false),
        );
        $this->assertEquals(count($submission1AggregateMetricDaos), count($submission1MetricValues));

        /** @var Tracker_AggregateMetricDao $submission1AggregateMetricDao */
        foreach ($submission1AggregateMetricDaos as $submission1AggregateMetricDao) {
            /** @var int $index */
            /** @var array $submission1MetricValue */
            foreach ($submission1MetricValues as $index => $submission1MetricValue) {
                if ($submission1AggregateMetricDao->getValue() == key($submission1MetricValue)) {
                    $submission1MetricValues[$index] = array(key($submission1MetricValue) => true);
                }
            }
        }
        /** @var int $index */
        /** @var float $submission1MetricValue */
        foreach ($submission1MetricValues as $index => $submission1MetricValue) {
            $this->assertTrue($submission1MetricValue[key($submission1MetricValue)]);
        }

        // Submission 2.

        $submission2AggregateMetricDaos = $aggregateMetricModel->computeAggregateMetricsForSubmission($submission2Dao);
        $submission2MetricValues = array(
            array(22.0 => false),
            array(38.0 => false),
            array(46.0 => false),
            array(54.0 => false),
            array(10.0 => false),
            array(2.0 => false),
        );
        $this->assertEquals(count($submission2AggregateMetricDaos), count($submission2MetricValues));

        /** @var Tracker_AggregateMetricDao $submission2AggregateMetricDao */
        foreach ($submission2AggregateMetricDaos as $submission2AggregateMetricDao) {
            /** @var int $index */
            /** @var array $submission2MetricValue */
            foreach ($submission2MetricValues as $index => $submission2MetricValue) {
                if ($submission2AggregateMetricDao->getValue() == key($submission2MetricValue)) {
                    $submission2MetricValues[$index] = array(key($submission2MetricValue) => true);
                }
            }
        }
        /** @var int $index */
        /** @var float $submission2MetricValue */
        foreach ($submission2MetricValues as $index => $submission2MetricValue) {
            $this->assertTrue($submission2MetricValue[key($submission2MetricValue)]);
        }

        // Test null input.
        $nullSubmissionAggregateMetrics = $aggregateMetricModel->computeAggregateMetricsForSubmission(null);
        $this->assertFalse($nullSubmissionAggregateMetrics);

        // Finally ensure that no new aggregate metrics have been saved.
        $submission1Metrics = $aggregateMetricModel->getAggregateMetricsForSubmission($submission1Dao);
        $submission1MetricsFinalCount = count($submission1Metrics);
        $submission2Metrics = $aggregateMetricModel->getAggregateMetricsForSubmission($submission2Dao);
        $submission2MetricsFinalCount = count($submission2Metrics);

        $this->assertEquals($submission1MetricsInitialCount, $submission1MetricsFinalCount);
        $this->assertEquals($submission2MetricsInitialCount, $submission2MetricsFinalCount);
    }

    /** test AggregateMetricModel updateAggregateMetricsForSubmission function */
    public function testUpdateAggregateMetricsForSubmission()
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

        // Keep the counts of saved metrics to ensure new ones are saved.
        $submission1Metrics = $aggregateMetricModel->updateAggregateMetricsForSubmission($submission1Dao);
        $submission1MetricsInitialCount = count($submission1Metrics);
        $submission2Metrics = $aggregateMetricModel->updateAggregateMetricsForSubmission($submission2Dao);
        $submission2MetricsInitialCount = count($submission2Metrics);

        // Create a new AMS, with a value of 5.0 for submission 1 and 10.0 for submission 2.
        /** @var Tracker_AggregateMetricSpecDao $greedyError25thPercentileAMSDao */
        $name = '25th Percentile Greedy error';
        $spec = "percentile('Greedy error', 25)";
        $greedyError25thPercentileAMSDao = $aggregateMetricSpecModel->createAggregateMetricSpec($producer100Dao, $name, $spec);

        $submission1AggregateMetricDaos = $aggregateMetricModel->updateAggregateMetricsForSubmission($submission1Dao);
        $submission1MetricValues = array(
            array(19.0 => false),
            array(11.0 => false),
            array(44.0 => false),
            array(36.0 => false),
            array(5.0 => false),
            array(1.0 => false),
        );
        $this->assertEquals(count($submission1AggregateMetricDaos), count($submission1MetricValues));

        /** @var Tracker_AggregateMetricDao $submission1AggregateMetricDao */
        foreach ($submission1AggregateMetricDaos as $submission1AggregateMetricDao) {
            /** @var int $index */
            /** @var array $submission1MetricValue */
            foreach ($submission1MetricValues as $index => $submission1MetricValue) {
                if ($submission1AggregateMetricDao->getValue() == key($submission1MetricValue)) {
                    $submission1MetricValues[$index] = array(key($submission1MetricValue) => true);
                }
            }
        }
        /** @var int $index */
        /** @var float $submission1MetricValue */
        foreach ($submission1MetricValues as $index => $submission1MetricValue) {
            $this->assertTrue($submission1MetricValue[key($submission1MetricValue)]);
        }

        // Add 4 trends with a scalar each and test again.
        $extraTrends = $this->createAdditionalGreedyErrorSubmission1Scalars();

        $submission1AggregateMetricDaos = $aggregateMetricModel->updateAggregateMetricsForSubmission($submission1Dao);
        $submission1MetricValues = array(
            array(23.0 => false),
            array(13.0 => false),
            array(44.0 => false),
            array(36.0 => false),
            array(6.0 => false),
            array(1.0 => false),
        );
        $this->assertEquals(count($submission1AggregateMetricDaos), count($submission1MetricValues));

        /** @var Tracker_AggregateMetricDao $submission1AggregateMetricDao */
        foreach ($submission1AggregateMetricDaos as $submission1AggregateMetricDao) {
            /** @var int $index */
            /** @var array $submission1MetricValue */
            foreach ($submission1MetricValues as $index => $submission1MetricValue) {
                if ($submission1AggregateMetricDao->getValue() == key($submission1MetricValue)) {
                    $submission1MetricValues[$index] = array(key($submission1MetricValue) => true);
                }
            }
        }
        /** @var int $index */
        /** @var float $submission1MetricValue */
        foreach ($submission1MetricValues as $index => $submission1MetricValue) {
            $this->assertTrue($submission1MetricValue[key($submission1MetricValue)]);
        }

        $this->deleteExtraTrends($extraTrends);

        // Retest after deletion.
        $submission1AggregateMetricDaos = $aggregateMetricModel->updateAggregateMetricsForSubmission($submission1Dao);
        $submission1MetricValues = array(
            array(19.0 => false),
            array(11.0 => false),
            array(44.0 => false),
            array(36.0 => false),
            array(5.0 => false),
            array(1.0 => false),
        );
        $this->assertEquals(count($submission1AggregateMetricDaos), count($submission1MetricValues));

        /** @var Tracker_AggregateMetricDao $submission1AggregateMetricDao */
        foreach ($submission1AggregateMetricDaos as $submission1AggregateMetricDao) {
            /** @var int $index */
            /** @var array $submission1MetricValue */
            foreach ($submission1MetricValues as $index => $submission1MetricValue) {
                if ($submission1AggregateMetricDao->getValue() == key($submission1MetricValue)) {
                    $submission1MetricValues[$index] = array(key($submission1MetricValue) => true);
                }
            }
        }
        /** @var int $index */
        /** @var float $submission1MetricValue */
        foreach ($submission1MetricValues as $index => $submission1MetricValue) {
            $this->assertTrue($submission1MetricValue[key($submission1MetricValue)]);
        }

        // Submission 2.

        $submission2AggregateMetricDaos = $aggregateMetricModel->updateAggregateMetricsForSubmission($submission2Dao);
        $submission2MetricValues = array(
            array(22.0 => false),
            array(38.0 => false),
            array(46.0 => false),
            array(54.0 => false),
            array(10.0 => false),
            array(2.0 => false),
        );
        $this->assertEquals(count($submission2AggregateMetricDaos), count($submission2MetricValues));

        /** @var Tracker_AggregateMetricDao $submission2AggregateMetricDao */
        foreach ($submission2AggregateMetricDaos as $submission2AggregateMetricDao) {
            /** @var int $index */
            /** @var array $submission2MetricValue */
            foreach ($submission2MetricValues as $index => $submission2MetricValue) {
                if ($submission2AggregateMetricDao->getValue() == key($submission2MetricValue)) {
                    $submission2MetricValues[$index] = array(key($submission2MetricValue) => true);
                }
            }
        }
        /** @var int $index */
        /** @var float $submission2MetricValue */
        foreach ($submission2MetricValues as $index => $submission2MetricValue) {
            $this->assertTrue($submission2MetricValue[key($submission2MetricValue)]);
        }

        // Test null input.
        $nullSubmissionAggregateMetrics = $aggregateMetricModel->updateAggregateMetricsForSubmission(null);
        $this->assertFalse($nullSubmissionAggregateMetrics);

        // Finally ensure that new aggregate metrics have been saved.
        $submission1Metrics = $aggregateMetricModel->getAggregateMetricsForSubmission($submission1Dao);
        $submission1MetricsFinalCount = count($submission1Metrics);
        $submission2Metrics = $aggregateMetricModel->getAggregateMetricsForSubmission($submission2Dao);
        $submission2MetricsFinalCount = count($submission2Metrics);

        $this->assertEquals($submission1MetricsInitialCount + 1, $submission1MetricsFinalCount);
        $this->assertEquals($submission2MetricsInitialCount + 1, $submission2MetricsFinalCount);
    }

    /** test AggregateMetricModel getAggregateMetricForSubmission function */
    public function testGetAggregateMetricForSubmission()
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

        // Keep the counts of saved metrics to ensure no new ones are saved.
        $submission1Metrics = $aggregateMetricModel->getAggregateMetricsForSubmission($submission1Dao);
        $submission1MetricsInitialCount = count($submission1Metrics);
        $submission2Metrics = $aggregateMetricModel->getAggregateMetricsForSubmission($submission2Dao);
        $submission2MetricsInitialCount = count($submission2Metrics);

        /** @var Tracker_AggregateMetricSpecDao $greedyError95thPercentileAMSDao */
        $greedyError95thPercentileAMSDao = $aggregateMetricSpecModel->load(1);
        /** @var Tracker_AggregateMetricSpecDao $greedyError55thPercentileAMSDao */
        $greedyError55thPercentileAMSDao = $aggregateMetricSpecModel->load(2);

        $aggregateMetricDao = $aggregateMetricModel->getAggregateMetricForSubmission($greedyError95thPercentileAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 19.0);

        $aggregateMetricDao = $aggregateMetricModel->getAggregateMetricForSubmission($greedyError95thPercentileAMSDao, $submission2Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 38.0);

        $aggregateMetricDao = $aggregateMetricModel->getAggregateMetricForSubmission($greedyError55thPercentileAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 11.0);

        $aggregateMetricDao = $aggregateMetricModel->getAggregateMetricForSubmission($greedyError55thPercentileAMSDao, $submission2Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 22.0);

        // Add 4 trends with a scalar each and test again, none of the metric values should change.
        $extraTrends = $this->createAdditionalGreedyErrorSubmission1Scalars();

        $aggregateMetricDao = $aggregateMetricModel->getAggregateMetricForSubmission($greedyError95thPercentileAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 19.0);

        $aggregateMetricDao = $aggregateMetricModel->getAggregateMetricForSubmission($greedyError55thPercentileAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 11.0);

        $this->deleteExtraTrends($extraTrends);

        // Retest after deletion, again none of the metric values should change.
        $aggregateMetricDao = $aggregateMetricModel->getAggregateMetricForSubmission($greedyError95thPercentileAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 19.0);

        $aggregateMetricDao = $aggregateMetricModel->getAggregateMetricForSubmission($greedyError55thPercentileAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 11.0);

        /** @var Tracker_AggregateMetricSpecDao $optimalError95thPercentileAMSDao */
        $optimalError95thPercentileAMSDao = $aggregateMetricSpecModel->load(3);
        /** @var Tracker_AggregateMetricSpecDao $optimalError55thPercentileAMSDao */
        $optimalError55thPercentileAMSDao = $aggregateMetricSpecModel->load(4);

        $aggregateMetricDao = $aggregateMetricModel->getAggregateMetricForSubmission($optimalError95thPercentileAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 44.0);

        $aggregateMetricDao = $aggregateMetricModel->getAggregateMetricForSubmission($optimalError95thPercentileAMSDao, $submission2Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 54.0);

        $aggregateMetricDao = $aggregateMetricModel->getAggregateMetricForSubmission($optimalError55thPercentileAMSDao, $submission1Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 36.0);

        $aggregateMetricDao = $aggregateMetricModel->getAggregateMetricForSubmission($optimalError55thPercentileAMSDao, $submission2Dao);
        $this->assertEquals($aggregateMetricDao->getValue(), 46.0);

        /** @var Tracker_AggregateMetricSpecDao $greedyDistance55thPercentileAMSDao */
        $greedyDistance55thPercentileAMSDao = $aggregateMetricSpecModel->load(5);

        $aggregateMetricDao = $aggregateMetricModel->getAggregateMetricForSubmission($greedyDistance55thPercentileAMSDao, $submission1Dao);
        // This has no trends that are key metrics.
        $this->assertEquals($aggregateMetricDao, false);

        /** @var Tracker_AggregateMetricSpecDao $optimalDistance55thPercentileAMSDao */
        $optimalDistance55thPercentileAMSDao = $aggregateMetricSpecModel->load(6);

        $aggregateMetricDao = $aggregateMetricModel->getAggregateMetricForSubmission($optimalDistance55thPercentileAMSDao, $submission1Dao);
        // Trends have no scalars.
        $this->assertEquals($aggregateMetricDao, false);

        // Test combinations of null inputs.
        $nullAMSAggregateMetricDao = $aggregateMetricModel->getAggregateMetricForSubmission(null, $submission1Dao);
        $this->assertFalse($nullAMSAggregateMetricDao);
        $nullSubmissionAggregateMetricDao = $aggregateMetricModel->getAggregateMetricForSubmission($optimalDistance55thPercentileAMSDao, null);
        $this->assertFalse($nullSubmissionAggregateMetricDao);
        $nullBothAggregateMetricDao = $aggregateMetricModel->getAggregateMetricForSubmission(null, null);
        $this->assertFalse($nullBothAggregateMetricDao);

        // Finally ensure that no new aggregate metrics have been saved.
        $submission1Metrics = $aggregateMetricModel->getAggregateMetricsForSubmission($submission1Dao);
        $submission1MetricsFinalCount = count($submission1Metrics);
        $submission2Metrics = $aggregateMetricModel->getAggregateMetricsForSubmission($submission2Dao);
        $submission2MetricsFinalCount = count($submission2Metrics);

        $this->assertEquals($submission1MetricsInitialCount, $submission1MetricsFinalCount);
        $this->assertEquals($submission2MetricsInitialCount, $submission2MetricsFinalCount);
    }

    /** test AggregateMetricModel getAggregateMetricsForSubmissions function */
    public function testGetAggregateMetricsForSubmissions()
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
        /** @var Tracker_SubmissionDao $submission3Dao */
        $submission3Dao = $submissionModel->load(3);
        /** @var Tracker_SubmissionDao $submission4Dao */
        $submission4Dao = $submissionModel->load(4);
        /** @var Tracker_SubmissionDao $submission5Dao */
        $submission5Dao = $submissionModel->load(5);
        /** @var Tracker_SubmissionDao $submission6Dao */
        $submission6Dao = $submissionModel->load(6);
        /** @var Tracker_SubmissionDao $submission7Dao */
        $submission7Dao = $submissionModel->load(7);

        $submissionDaos = array(
            $submission1Dao,
            $submission2Dao,
            $submission3Dao,
            $submission4Dao,
            $submission5Dao,
            $submission6Dao,
            $submission7Dao,
        );

        // Compute and save metrics for all submissions.
        /** @var Tracker_SubmissionDao $submissionDao */
        foreach ($submissionDaos as $submissionDao) {
            $submissionMetrics = $aggregateMetricModel->updateAggregateMetricsForSubmission($submissionDao);
        }

        /** @var Tracker_AggregateMetricSpecDao $greedyError95thPercentileAMSDao */
        $greedyError95thPercentileAMSDao = $aggregateMetricSpecModel->load(1);
        $aggregateMetricsForSubmissions = $aggregateMetricModel->getAggregateMetricsForSubmissions($greedyError95thPercentileAMSDao, $submissionDaos);
        $expectedValues = array(4.0, 4.0, 4.0, 4.0, 4.0, 38.0, 19.0);

        /** @var int $valueIndex */
        $valueIndex = 0;
        /** @var Tracker_AggregateMetricDao $aggregateMetricDao */
        foreach ($aggregateMetricsForSubmissions as $aggregateMetricDao) {
            $this->assertEquals($aggregateMetricDao->getValue(), $expectedValues[$valueIndex]);
            ++$valueIndex;
        }

        // Test a smaller subset.
        $submissionSubset = array(
            $submission1Dao,
            $submission2Dao,
            $submission3Dao,
        );
        $aggregateMetricsForSubmissions = $aggregateMetricModel->getAggregateMetricsForSubmissions($greedyError95thPercentileAMSDao, $submissionSubset);
        $expectedValues = array(4.0, 38.0, 19.0);

        $valueIndex = 0;
        /** @var Tracker_AggregateMetricDao $aggregateMetricDao */
        foreach ($aggregateMetricsForSubmissions as $aggregateMetricDao) {
            $this->assertEquals($aggregateMetricDao->getValue(), $expectedValues[$valueIndex]);
            ++$valueIndex;
        }

        // Change around the order of submissions, should still get the same result.
        $submissionDaosReordered = array(
            $submission3Dao,
            $submission2Dao,
            $submission1Dao,
            $submission4Dao,
            $submission5Dao,
            $submission7Dao,
            $submission6Dao,
        );

        $aggregateMetricsForSubmissions = $aggregateMetricModel->getAggregateMetricsForSubmissions($greedyError95thPercentileAMSDao, $submissionDaosReordered);
        $expectedValues = array(4.0, 4.0, 4.0, 4.0, 4.0, 38.0, 19.0);

        /** @var int $valueIndex */
        $valueIndex = 0;
        /** @var Tracker_AggregateMetricDao $aggregateMetricDao */
        foreach ($aggregateMetricsForSubmissions as $aggregateMetricDao) {
            $this->assertEquals($aggregateMetricDao->getValue(), $expectedValues[$valueIndex]);
            ++$valueIndex;
        }

        /** @var Tracker_AggregateMetricSpecDao $greedyError55thPercentileAMSDao */
        $greedyError55thPercentileAMSDao = $aggregateMetricSpecModel->load(2);
        $aggregateMetricsForSubmissions = $aggregateMetricModel->getAggregateMetricsForSubmissions($greedyError55thPercentileAMSDao, $submissionDaos);
        $expectedValues = array(2.0, 2.0, 2.0, 2.0, 2.0, 22.0, 11.0);

        $valueIndex = 0;
        /** @var Tracker_AggregateMetricDao $aggregateMetricDao */
        foreach ($aggregateMetricsForSubmissions as $aggregateMetricDao) {
            $this->assertEquals($aggregateMetricDao->getValue(), $expectedValues[$valueIndex]);
            ++$valueIndex;
        }

        /** @var Tracker_AggregateMetricSpecDao $optimalError95thPercentileAMSDao */
        $optimalError95thPercentileAMSDao = $aggregateMetricSpecModel->load(3);
        $aggregateMetricsForSubmissions = $aggregateMetricModel->getAggregateMetricsForSubmissions($optimalError95thPercentileAMSDao, $submissionDaos);
        $expectedValues = array(7.0, 7.0, 7.0, 7.0, 7.0, 54.0, 44.0);

        $valueIndex = 0;
        /** @var Tracker_AggregateMetricDao $aggregateMetricDao */
        foreach ($aggregateMetricsForSubmissions as $aggregateMetricDao) {
            $this->assertEquals($aggregateMetricDao->getValue(), $expectedValues[$valueIndex]);
            ++$valueIndex;
        }

        /** @var Tracker_AggregateMetricSpecDao $optimalError55thPercentileAMSDao */
        $optimalError55thPercentileAMSDao = $aggregateMetricSpecModel->load(4);
        $aggregateMetricsForSubmissions = $aggregateMetricModel->getAggregateMetricsForSubmissions($optimalError55thPercentileAMSDao, $submissionDaos);
        $expectedValues = array(5.0, 5.0, 5.0, 5.0, 5.0, 46.0, 36.0);

        $valueIndex = 0;
        /** @var Tracker_AggregateMetricDao $aggregateMetricDao */
        foreach ($aggregateMetricsForSubmissions as $aggregateMetricDao) {
            $this->assertEquals($aggregateMetricDao->getValue(), $expectedValues[$valueIndex]);
            ++$valueIndex;
        }

        // Test combinations of null inputs.
        $nullAMSAggregateMetricDao = $aggregateMetricModel->getAggregateMetricsForSubmissions(null, $submissionDaos);
        $this->assertFalse($nullAMSAggregateMetricDao);
        $nullSubmissionAggregateMetricDao = $aggregateMetricModel->getAggregateMetricsForSubmissions($optimalError55thPercentileAMSDao, null);
        $this->assertFalse($nullSubmissionAggregateMetricDao);
        $nullBothAggregateMetricDao = $aggregateMetricModel->getAggregateMetricsForSubmissions(null, null);
        $this->assertFalse($nullBothAggregateMetricDao);
    }

    /** test AggregateMetricModel getAggregateMetricsSeries function */
    public function testGetAggregateMetricsSeries()
    {
        /** @var Tracker_ProducerModel $producerModel */
        $producerModel = MidasLoader::loadModel('Producer', 'tracker');
        /** @var Tracker_SubmissionModel $submissionModel */
        $submissionModel = MidasLoader::loadModel('Submission', 'tracker');
        /** @var AggregateMetricSpecModel $aggregateMetricSpecModel */
        $aggregateMetricSpecModel = MidasLoader::loadModel('AggregateMetricSpec', 'tracker');
        $greedyError95thPercentileAMSDao = $aggregateMetricSpecModel->load(1);
        /** @var AggregateMetricModel $aggregateMetricModel */
        $aggregateMetricModel = MidasLoader::loadModel('AggregateMetric', 'tracker');

        /** @var Tracker_ProducerDao $producer100Dao */
        $producer100Dao = $producerModel->load(100);

        // Calculate aggregate metrics on submissions 1..7
        /** @var int $i */
        for($i = 1; $i < 8; $i = $i + 1) {
            /** @var Tracker_SubmissionDao $submissionDao */
            $submissionDao = $submissionModel->load($i);
            /** @var array $aggregateMetrics */
            $aggregateMetrics = $aggregateMetricModel->updateAggregateMetricForSubmission($greedyError95thPercentileAMSDao, $submissionDao);
        }

        // Since today's date is at least a week past 2016-02-07, unless you are time travelling
        // or your system clock is off, this should return an empty array.
        $this->assertTrue(empty($aggregateMetricModel->getAggregateMetricsSeries($producer100Dao)));

        $aggregateMetricSeries = $aggregateMetricModel->getAggregateMetricsSeries($producer100Dao, date('2016-02-07 23:59:59'));
        $this->assertFalse(empty($aggregateMetricSeries));
        $this->assertEquals(5, count($aggregateMetricSeries));
        $expectedSeries = array(
            '95th Percentile Optimal error' => array(7, 7, 7, 7, 7, 54, 44),
            '95th Percentile Greedy error' => array(4, 4, 4, 4, 4, 38, 19),
            '55th Percentile Optimal error' => array(5, 5, 5, 5, 5, 46, 36),
            '55th Percentile Greedy error' => array(2, 2, 2, 2, 2, 22, 11),
            '0th Percentile Greedy error' => array(1, 1, 1, 1, 1, 2, 1),
        );
        /** @var string $expectedSeriesKey */
        /** @var array $expectedSeriesValues */
        foreach ($expectedSeries as $expectedSeriesKey => $expectedSeriesValues) {
            $this->assertTrue(array_key_exists($expectedSeriesKey, $aggregateMetricSeries));
            $this->assertEquals($expectedSeriesValues, $aggregateMetricSeries[$expectedSeriesKey]);
        }

        // Get the series for 5 days.
        $aggregateMetricSeries = $aggregateMetricModel->getAggregateMetricsSeries($producer100Dao, date('2016-02-07 23:59:59'), 5);
        $this->assertFalse(empty($aggregateMetricSeries));
        $this->assertEquals(5, count($aggregateMetricSeries));
        $expectedSeries = array(
            '95th Percentile Optimal error' => array(7, 7, 7, 54, 44),
            '95th Percentile Greedy error' => array(4, 4, 4, 38, 19),
            '55th Percentile Optimal error' => array(5, 5, 5, 46, 36),
            '55th Percentile Greedy error' => array(2, 2, 2, 22, 11),
            '0th Percentile Greedy error' => array(1, 1, 1, 2, 1),
        );
        /** @var string $expectedSeriesKey */
        /** @var array $expectedSeriesValues */
        foreach ($expectedSeries as $expectedSeriesKey => $expectedSeriesValues) {
            $this->assertTrue(array_key_exists($expectedSeriesKey, $aggregateMetricSeries));
            $this->assertEquals($expectedSeriesValues, $aggregateMetricSeries[$expectedSeriesKey]);
        }

        // Get the series for 5 days starting back 2 days.
        $aggregateMetricSeries = $aggregateMetricModel->getAggregateMetricsSeries($producer100Dao, date('2016-02-05 23:59:59'), 5);
        $this->assertFalse(empty($aggregateMetricSeries));
        $this->assertEquals(5, count($aggregateMetricSeries));
        $expectedSeries = array(
            '95th Percentile Optimal error' => array(7, 7, 7, 7, 7),
            '95th Percentile Greedy error' => array(4, 4, 4, 4, 4),
            '55th Percentile Optimal error' => array(5, 5, 5, 5, 5),
            '55th Percentile Greedy error' => array(2, 2, 2, 2, 2),
            '0th Percentile Greedy error' => array(1, 1, 1, 1, 1),
        );
        /** @var string $expectedSeriesKey */
        /** @var array $expectedSeriesValues */
        foreach ($expectedSeries as $expectedSeriesKey => $expectedSeriesValues) {
            $this->assertTrue(array_key_exists($expectedSeriesKey, $aggregateMetricSeries));
            $this->assertEquals($expectedSeriesValues, $aggregateMetricSeries[$expectedSeriesKey]);
        }
    }
}
