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

/** Test the AggregateMetricSpecification. */
class Tracker_AggregateMetricSpecificationModelTest extends DatabaseTestCase
{
    /** Set up tests. */
    public function setUp()
    {
        $this->setupDatabase(array('default')); // core dataset
        $this->setupDatabase(array('aggregateMetric'), 'tracker'); // module dataset
        $this->enabledModules = array('tracker');
        parent::setUp();
    }

    /** test AggregateMetricSpecificationModel getProducerAggregateMetricSpecifications function */
    public function testGetProducerAggregateMetricSpecifications()
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

        /** @var AggregateMetricSpecificationModel $aggregateMetricSpecificationModel */
        $aggregateMetricSpecificationModel = MidasLoader::loadModel('AggregateMetricSpecification', 'tracker');
        $producerAggregateMetricSpecificationDaos = $aggregateMetricSpecificationModel->getProducerAggregateMetricSpecifications($producerDao);
        /** @var Tracker_AggregateMetricSpecificationDao $producerSpec */
        foreach ($producerAggregateMetricSpecificationDaos as $producerSpec) {
            if (array_key_exists($producerSpec->getAggregateMetricSpecificationId(), $spec1Ids)) {
                $spec1Ids[$producerSpec->getAggregateMetricSpecificationId()] = true;
            }
        }
        /** @var string $specId */
        /** @var bool $found */
        foreach ($spec1Ids as $specId => $found) {
            $this->assertTrue($found);
        }
    }

    /** test AggregateMetricSpecificationModel getSubmissionAggregateMetricSpecifications function */
    public function testGetSubmissionAggregateMetricSpecifications()
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

        /** @var AggregateMetricSpecificationModel $aggregateMetricSpecificationModel */
        $aggregateMetricSpecificationModel = MidasLoader::loadModel('AggregateMetricSpecification', 'tracker');
        $submissionAggregateMetricSpecificationDaos = $aggregateMetricSpecificationModel->getSubmissionAggregateMetricSpecifications($submissionDao1);
        /** @var Tracker_AggregateMetricSpecificationDao $submissionSpec */
        foreach ($submissionAggregateMetricSpecificationDaos as $submissionSpec) {
            if (array_key_exists($submissionSpec->getAggregateMetricSpecificationId(), $spec1Ids)) {
                $spec1Ids[$submissionSpec->getAggregateMetricSpecificationId()] = true;
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

        /** @var AggregateMetricSpecificationModel $aggregateMetricSpecificationModel */
        $aggregateMetricSpecificationModel = MidasLoader::loadModel('AggregateMetricSpecification', 'tracker');
        $submissionAggregateMetricSpecificationDaos = $aggregateMetricSpecificationModel->getSubmissionAggregateMetricSpecifications($submissionDao2);
        /** @var Tracker_AggregateMetricSpecificationDao $submissionSpec */
        foreach ($submissionAggregateMetricSpecificationDaos as $submissionSpec) {
            if (array_key_exists($submissionSpec->getAggregateMetricSpecificationId(), $spec2Ids)) {
                $spec2Ids[$submissionSpec->getAggregateMetricSpecificationId()] = true;
            }
        }
        /** @var string $specId */
        /** @var bool $found */
        foreach ($spec2Ids as $specId => $found) {
            $this->assertTrue($found);
        }
    }
}
