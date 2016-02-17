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

/** Test the AggregateMetricSpecification and AggregateMetric models. */
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

    /** testAggregateMetricModel */
    public function testAggregateMetricModel()
    {
        /** @var Tracker_ProducerModel $producerModel */
        $producerModel = MidasLoader::loadModel('Producer', 'tracker');
        /** @var Tracker_ProducerDao $producerDao */
        $producerDao = $producerModel->load(100);


        // Create an AggregateMetricSpecification for Greedy error 95th Percentile.

        /** @var Tracker_AggregateMetricSpecificationDao $aggregateMetricSpecificationDao */
        $aggregateMetricSpecificationDao = MidasLoader::newDao('AggregateMetricSpecificationDao', 'tracker');
        $aggregateMetricSpecificationDao->setProducerId($producerDao->getProducerId());
        $aggregateMetricSpecificationDao->setBranch('master');
        $aggregateMetricSpecificationDao->setName('95th Percentile Greedy error');
        $schema = "percentile('Greedy error', 95)";
        $aggregateMetricSpecificationDao->setSchema($schema);
        // Leave description, value and comparison as default.

        /** @var AggregateMetricSpecificationModel $aggregateMetricSpecificationModel */
        $aggregateMetricSpecificationModel = MidasLoader::loadModel('AggregateMetricSpecification', 'tracker');
        $aggregateMetricSpecificationModel->save($aggregateMetricSpecificationDao);

        // Create an AggregateMetric tied to this specification for submission_id 1.

        /** @var Tracker_SubmissionModel $submissionModel */
        $submissionModel = MidasLoader::loadModel('Submission', 'tracker');
        /** @var Tracker_SubmissionDao $submissionDao */
        $submissionDao = $submissionModel->load(1);

        /** @var AggregateMetricModel $aggregateMetricModel */
        $aggregateMetricModel = MidasLoader::loadModel('AggregateMetric', 'tracker');
        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($aggregateMetricSpecificationDao, $submissionDao);
        $this->assertEquals($aggregateMetricDao->getValue(), 19.0);

        // Test a different percentile, 50.
        /** @var Tracker_AggregateMetricSpecificationDao $aggregateMetricSpecificationDao */
        $aggregateMetricSpecificationDao = MidasLoader::newDao('AggregateMetricSpecificationDao', 'tracker');
        $aggregateMetricSpecificationDao->setProducerId($producerDao->getProducerId());
        $aggregateMetricSpecificationDao->setBranch('master');
        $aggregateMetricSpecificationDao->setName('50th Percentile Greedy error');
        $schema = "percentile('Greedy error', 50)";
        $aggregateMetricSpecificationDao->setSchema($schema);
        // Leave description, value and comparison as default.
        $aggregateMetricSpecificationModel->save($aggregateMetricSpecificationDao);

        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($aggregateMetricSpecificationDao, $submissionDao);
        $this->assertEquals($aggregateMetricDao->getValue(), 10.0);

        // Create an AggregateMetric tied to this specification for submission_id 2.

        /** @var Tracker_SubmissionDao $submissionDao */
        $submissionDao = $submissionModel->load(2);
        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($aggregateMetricSpecificationDao, $submissionDao);
        // 50th percentile.
        $this->assertEquals($aggregateMetricDao->getValue(), 20.0);


        // Create an AggregateMetricSpecification for MinMax error 95th Percentile.

        /** @var Tracker_AggregateMetricSpecificationDao $aggregateMetricSpecificationDao */
        $aggregateMetricSpecificationDao = MidasLoader::newDao('AggregateMetricSpecificationDao', 'tracker');
        $aggregateMetricSpecificationDao->setProducerId($producerDao->getProducerId());
        $aggregateMetricSpecificationDao->setBranch('master');
        $aggregateMetricSpecificationDao->setName('95th Percentile MinMax error');
        $schema = "percentile('MinMax error', 95)";
        $aggregateMetricSpecificationDao->setSchema($schema);
        // Leave description, value and comparison as default.
        $aggregateMetricSpecificationModel->save($aggregateMetricSpecificationDao);

        // Create an AggregateMetric tied to this specification for submission_id 1.

        /** @var Tracker_SubmissionDao $submissionDao */
        $submissionDao = $submissionModel->load(1);
        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($aggregateMetricSpecificationDao, $submissionDao);
        $this->assertEquals($aggregateMetricDao->getValue(), 44.0);

        // Create an AggregateMetric tied to this specification for submission_id 2.

        /** @var Tracker_SubmissionDao $submissionDao */
        $submissionDao = $submissionModel->load(2);
        $aggregateMetricDao = $aggregateMetricModel->computeAggregateMetricForSubmission($aggregateMetricSpecificationDao, $submissionDao);
        $this->assertEquals($aggregateMetricDao->getValue(), 54.0);
    }
}
