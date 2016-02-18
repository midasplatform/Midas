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

require_once BASE_PATH.'/modules/tracker/models/base/AggregateMetricModelBase.php';

/** AggregateMetric model for the tracker module. */
class Tracker_AggregateMetricModel extends Tracker_AggregateMetricModelBase
{
    /**
     * Compute a percentile value out of the passed in array.
     *
     * @param array values the set of values to compute percentile over
     * @param array params an array with the first element containing the desired percentile value
     * @return false | float the desired percentile value extracted from values
     */
    protected function percentile($values, $params)
    {
        if (!$params) {
            return false;
        }
        $percentile = $params[0];
        asort($values);
        $ind = round(($percentile / 100.0) * count($values)) - 1;

        return $values[$ind];
    }

    /**
     * Compute an aggregate metric for the submission based on the specification.
     *
     * @param Tracker_AggregateMetricSpecificationDao $aggregateMetricSpecificationDao specification DAO
     * @param Tracker_SubmissionDao $submissionDao submission DAO
     * @return false | Tracker_AggregateMetricDao metric DAO computed on the submission from the specification
     */
    public function computeAggregateMetricForSubmission($aggregateMetricSpecificationDao, $submissionDao)
    {
        if (is_null($aggregateMetricSpecificationDao) || $aggregateMetricSpecificationDao === false) {
            return false;
        }
        if (is_null($submissionDao) || $submissionDao === false) {
            return false;
        }

        // Expect schema like "percentile('Greedy max distance', 95)"
        preg_match("/(\w+)\((.*)\)/", $aggregateMetricSpecificationDao->getSchema(), $matches);
        $aggregationMethod = $matches[1];
        $params = explode(',', $matches[2]);
        $metricName = str_replace("'", '', $params[0]);
        $params = array_slice($params, 1);

        // Get the list of relevant trend_ids.
        $sql = $this->database->select()->setIntegrityCheck(false)
            ->from('tracker_trend', array('trend_id'))
            ->where('key_metric = ?', 1)
            ->where('producer_id = ?', $aggregateMetricSpecificationDao->getProducerId())
            ->where('metric_name = ?', $metricName);
        $rows = $this->database->fetchAll($sql);
        if (count($rows) === 0) {
            return false;
        };
        $trendIds = array();
        /** @var Zend_Db_Table_Row_Abstract $row */
        foreach ($rows as $row) {
            $trendIds[] = $row['trend_id'];
        }

        // Get all the scalar values from these trends in the submission.
        $sql = $this->database->select()->setIntegrityCheck(false)
            ->from('tracker_scalar', array('value'))
            ->where('submission_id = ?', $submissionDao->getSubmissionId())
            ->where('branch = ?', $aggregateMetricSpecificationDao->getBranch())
            ->where('trend_id IN (?)', $trendIds);
        $rows = $this->database->fetchAll($sql);
        if (count($rows) === 0) {
            return false;
        };
        $values = array();
        /** @var Zend_Db_Table_Row_Abstract $row */
        foreach ($rows as $row) {
            $values[] = floatval($row['value']);
        }

        $computedValue = $this->$aggregationMethod($values, $params);
        if ($computedValue === false) {
            return false;
        } else {
            /** @var Tracker_AggregateMetricDao $aggregateMetricDao */
            $aggregateMetricDao = MidasLoader::newDao('AggregateMetricDao', 'tracker');
            $aggregateMetricDao->setAggregateMetricSpecificationId($aggregateMetricSpecificationDao->getAggregateMetricSpecificationId());
            $aggregateMetricDao->setSubmissionId($submissionDao->getSubmissionId());
            $aggregateMetricDao->setValue($computedValue);
            $this->save($aggregateMetricDao);

            return $aggregateMetricDao;
        }
    }
}
