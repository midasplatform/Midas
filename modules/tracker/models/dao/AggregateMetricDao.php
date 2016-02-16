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

/**
 * AggregateMetric DAO for the tracker module.
 *
 * @method int getAggregateMetricId()
 * @method void setAggregateMetricId(int $aggregateMetricId)
 * @method int getAggregateMetricSpecificationId()
 * @method void setAggregateMetricSpecificationId(int $aggregateMetricSpecificationId)
 * @method int getSubmissionId()
 * @method void setSubmissionId(int $submissionId)
 * @method float getValue()
 * @method void setValue(float $value)
 * @method Tracker_AggregateMetricSpecificationDao getAggregateMetricSpecification()
 * @method void setAggregateMetricSpecification(Tracker_AggregateMetricSpecificationDao $aggregateMetricSpecificationDao)
 * @method Tracker_SubmissionDao getSubmission()
 * @method void setSubmission(Tracker_SubmissionDao $submissionDao)
 */
class Tracker_AggregateMetricDao extends Tracker_AppDao
{
    /** @var string */
    public $_model = 'AggregateMetric';

    /** @var string */
    public $_module = 'tracker';

}
