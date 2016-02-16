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

/** AggregateMetric base model class for the tracker module. */
abstract class Tracker_AggregateMetricModelBase extends Tracker_AppModel
{
    /** Constructor. */
    public function __construct()
    {
        parent::__construct();

        $this->_name = 'tracker_aggregate_metric';
        $this->_key = 'aggregate_metric_id';
        $this->_mainData = array(
            'aggregate_metric_id' => array('type' => MIDAS_DATA),
            'aggregate_metric_specification_id' => array('type' => MIDAS_DATA),
            'submission_id' => array('type' => MIDAS_DATA),
            'value' => array('type' => MIDAS_DATA),
            'aggregate_metric_specification' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'AggregateMetricSpecification',
                'module' => $this->moduleName,
                'parent_column' => 'aggregate_metric_specification_id',
                'child_column' => 'aggregate_metric_specification_id',
            ),
            'submission' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'Submission',
                'module' => $this->moduleName,
                'parent_column' => 'submission_id',
                'child_column' => 'submission_id',
            ),
        );

        $this->initialize();
    }

}
