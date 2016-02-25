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

/** AggregateMetricSpec base model class for the tracker module. */
abstract class Tracker_AggregateMetricSpecModelBase extends Tracker_AppModel
{
    /** Constructor. */
    public function __construct()
    {
        parent::__construct();

        $this->_name = 'tracker_aggregate_metric_spec';
        $this->_daoName = 'AggregateMetricSpecDao';
        $this->_key = 'aggregate_metric_spec_id';
        $this->_mainData = array(
            'aggregate_metric_spec_id' => array('type' => MIDAS_DATA),
            'producer_id' => array('type' => MIDAS_DATA),
            'branch' => array('type' => MIDAS_DATA),
            'name' => array('type' => MIDAS_DATA),
            'description' => array('type' => MIDAS_DATA),
            'spec' => array('type' => MIDAS_DATA),
            'value' => array('type' => MIDAS_DATA),
            'comparison' => array('type' => MIDAS_DATA),
            'producer' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'Producer',
                'module' => $this->moduleName,
                'parent_column' => 'producer_id',
                'child_column' => 'producer_id',
            ),
        );

        $this->initialize();
    }

    /**
     * Create an AggregateMetricSpecDao from the inputs.
     *
     * @param Tracker_ProducerDao $producerDao producer DAO
     * @param string $name the name of the aggregate metric spec
     * @param string $spec the spec for the aggregate metric spec
     * @param string $branch the branch of the aggregate metric spec (defaults to 'master')
     * @param false | string $description the description for the aggregate metric spec
     * @param false | string $value the value for the aggregate metric spec threshold
     * @param false | string $comparison the comparison for the aggregate metric spec threshold,
     * one of ['>', '<', '>=', '<', '<=', '==', '!=']
     * @return false | Tracker_AggregateMetricSpecDao created from inputs
     */
    public function createAggregateMetricSpec($producerDao, $name, $spec, $branch = 'master', $description = false, $value = false, $comparison = false)
    {
        if (is_null($producerDao) || $producerDao === false) {
            return false;
        }

        /** @var Tracker_AggregateMetricSpecDao $aggregateMetricSpecDao */
        $aggregateMetricSpecDao = MidasLoader::newDao('AggregateMetricSpecDao', 'tracker');
        $aggregateMetricSpecDao->setProducerId($producerDao->getProducerId());
        $aggregateMetricSpecDao->setBranch($branch);
        $aggregateMetricSpecDao->setName($name);
        $aggregateMetricSpecDao->setSpec($spec);
        if ($description) {
            $aggregateMetricSpecDao->setDescription($description);
        } else {
            $aggregateMetricSpecDao->setDescription('');
        }
        if ($value) {
            $aggregateMetricSpecDao->setValue($value);
        }
        if ($comparison) {
            $aggregateMetricSpecDao->setComparison($comparison);
        } else {
            $aggregateMetricSpecDao->setComparison('');
        }
        $this->save($aggregateMetricSpecDao);

        return $aggregateMetricSpecDao;
    }

    /**
     * Return all AggregateMetricSpecDaos tied to the producer.
     *
     * @param Tracker_ProducerDao $producerDao producer DAO
     * @return false | array AggregateMetricSpec DOAs all AggregateMetricSpecDaos linked to the ProducerDao
     */
    public function getAggregateMetricSpecsForProducer($producerDao)
    {
        if (is_null($producerDao) || $producerDao === false) {
            return false;
        }

        return $this->findBy('producer_id', $producerDao->getProducerId());
    }

    /**
     * Return all AggregateMetricSpecDaos tied to the submission, via the producer.
     *
     * @param Tracker_SubmissionDao $submissionDao submission DAO
     * @return false | array AggregateMetricSpec DOAs all AggregateMetricSpecDaos linked to the
     * SubmissionDao via its linked producer
     */
    public function getAggregateMetricSpecsForSubmission($submissionDao)
    {
        if (is_null($submissionDao) || $submissionDao === false) {
            return false;
        }

        return $this->getAggregateMetricSpecsForProducer($submissionDao->getProducer());
    }
}
