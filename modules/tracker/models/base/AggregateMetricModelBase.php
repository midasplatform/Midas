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
        $this->_daoName = 'AggregateMetricDao';
        $this->_key = 'aggregate_metric_id';
        $this->_mainData = array(
            'aggregate_metric_id' => array('type' => MIDAS_DATA),
            'aggregate_metric_spec_id' => array('type' => MIDAS_DATA),
            'submission_id' => array('type' => MIDAS_DATA),
            'value' => array('type' => MIDAS_DATA),
            'aggregate_metric_spec' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'AggregateMetricSpec',
                'module' => $this->moduleName,
                'parent_column' => 'aggregate_metric_spec_id',
                'child_column' => 'aggregate_metric_spec_id',
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

    /**
     * Return a sorted array of input scalars that would be used by an aggregate metric for the submission based on the spec.
     *
     * @param Tracker_AggregateMetricSpecDao $aggregateMetricSpecDao spec DAO
     * @param Tracker_SubmissionDao $submissionDao submission DAO
     * @return false | array array of scalar values that would be input to the aggregate metric
     */
    abstract public function getAggregateMetricInputValuesForSubmission($aggregateMetricSpecDao, $submissionDao);

    /**
     * Delete any existing AggregateMetric for the submission and spec, then compute and save
     * an AggregateMetric for the submission and spec, returning the AggregateMetric.
     *
     * @param Tracker_AggregateMetricSpecDao $aggregateMetricSpecDao spec DAO
     * @param Tracker_SubmissionDao $submissionDao submission DAO
     * @return false | Tracker_AggregateMetricDao metric DAO computed on the submission from the spec
     */
    abstract public function updateAggregateMetricForSubmission($aggregateMetricSpecDao, $submissionDao);

    /**
     * Compute on the fly the AggregateMetricDao for the submission and the
     * aggregate metric spec, without saving any results.
     *
     * @param Tracker_AggregateMetricSpecDao $aggregateMetricSpecDao spec DAO
     * @param Tracker_SubmissionDao $submissionDao submission DAO
     * @return false | Tracker_AggregateMetricDao metric DAO computed on the submission from the spec
     */
    abstract public function computeAggregateMetricForSubmission($aggregateMetricSpecDao, $submissionDao);

    /**
     * Compute on the fly all AggregateMetricDaos for the submission, without
     * saving any results.
     *
     * @param Tracker_SubmissionDao $submissionDao submission DAO
     * @return false | array AggregateMetric DAOs all AggregateMetricDaos for the
     * SubmissionDao
     */
    abstract public function computeAggregateMetricsForSubmission($submissionDao);

    /**
     * Delete all existing AggregateMetrics for the submission, then compute and save all
     * AggregateMetrics for the submission, returning the AggregateMetrics.
     *
     * @param Tracker_SubmissionDao $submissionDao submission DAO
     * @return false | array AggregateMetric DAOs all AggregateMetricDaos for the
     * SubmissionDao
     */
    abstract public function updateAggregateMetricsForSubmission($submissionDao);

    /**
     * Return one existing AggregateMetric tied to the submission and spec.
     *
     * @param Tracker_AggregateMetricSpecDao $aggregateMetricSpecDao spec DAO
     * @param Tracker_SubmissionDao $submissionDao submission DAO
     * @return false | Tracker_AggregateMetricDao the AggregateMetricDao linked to the
     * SubmissionDao and AggregateMetricSpecDao
     */
    abstract public function getAggregateMetricForSubmission($aggregateMetricSpecDao, $submissionDao);

    /**
     * Return a list of submission ids mapped to an existing aggregate metric for
     * that submission and the single aggregate metric spec, sorted in ascending order
     * of submission submit_time.
     *
     * @param Tracker_AggregateMetricSpecDao $aggregateMetricSpecDao spec DAO
     * @param Tracker_SubmissionDao $submissionDao submission DAO
     * @return false | array keys are submission_id and values are Tracker_AggregateMetricDao
     * for that SubmissionDao and AggregateMetricSpecDao, sorted in ascending order of
     * SubmissionDao submit_time.
     */
    abstract public function getAggregateMetricsForSubmissions($aggregateMetricSpecDao, $submissionDaos);

    /**
     * Return all AggregateMetricDaos tied to the submission.
     *
     * @param Tracker_SubmissionDao $submissionDao submission DAO
     * @return false | array AggregateMetric DAOs all AggregateMetricDaos linked to the
     * SubmissionDao
     */
    public function getAggregateMetricsForSubmission($submissionDao)
    {
        if (is_null($submissionDao) || $submissionDao === false) {
            return false;
        }

        return $this->findBy('submission_id', $submissionDao->getSubmissionId());
    }
}
