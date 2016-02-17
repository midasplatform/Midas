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

/** AggregateMetricSpecification base model class for the tracker module. */
abstract class Tracker_AggregateMetricSpecificationModelBase extends Tracker_AppModel
{
    /** Constructor. */
    public function __construct()
    {
        parent::__construct();

        $this->_name = 'tracker_aggregate_metric_specification';
        $this->_daoName = 'AggregateMetricSpecificationDao';
        $this->_key = 'aggregate_metric_specification_id';
        $this->_mainData = array(
            'aggregate_metric_specification_id' => array('type' => MIDAS_DATA),
            'producer_id' => array('type' => MIDAS_DATA),
            'branch' => array('type' => MIDAS_DATA),
            'name' => array('type' => MIDAS_DATA),
            'description' => array('type' => MIDAS_DATA),
            'schema' => array('type' => MIDAS_DATA),
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
     * Create an AggregateMetricSpecificationDao from the inputs.
     *
     * @param Tracker_ProducerDao $producerDao producer DAO
     * @param string $name the name of the aggregate metric specification
     * @param string $schema the schema for the aggregate metric specification
     * @param string $branch the branch of the aggregate metric specification (defaults to 'master')
     * @param false | string $description the description for the aggregate metric specification
     * @param false | string $value the value for the aggregate metric specification threshold
     * @param false | string $comparison the comparison for the aggregate metric specification threshold,
     * one of ['>', '<', '>=', '<', '<=', '==', '!=']
     * @return Tracker_AggregateMetricSpecificationDao created from inputs
     */
    public function createAggregateMetricSpecification($producerDao, $name, $schema, $branch = 'master', $description = false, $value = false, $comparison = false) {
        /** @var Tracker_AggregateMetricSpecificationDao $aggregateMetricSpecificationDao */
        $aggregateMetricSpecificationDao = MidasLoader::newDao('AggregateMetricSpecificationDao', 'tracker');
        $aggregateMetricSpecificationDao->setProducerId($producerDao->getProducerId());
        $aggregateMetricSpecificationDao->setBranch($branch);
        $aggregateMetricSpecificationDao->setName($name);
        $aggregateMetricSpecificationDao->setSchema($schema);
        if ($description) {
            $aggregateMetricSpecificationDao->setDescription($description);
        }
        if ($value) {
            $aggregateMetricSpecificationDao->setValue($value);
        }
        if ($comparison) {
            $aggregateMetricSpecificationDao->setComparison($comparison);
        }
        $this->save($aggregateMetricSpecificationDao);
        return $aggregateMetricSpecificationDao;
    }

}
