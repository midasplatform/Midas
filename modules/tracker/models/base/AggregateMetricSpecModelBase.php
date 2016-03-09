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
     * Create a user notification tied to the aggregate metric spec.
     *
     * @param Tracker_AggregateMetricSpecDao $aggregateMetricSpecDao aggregateMetricSpec DAO
     * @param UserDao $userDao user DAO
     * @return bool true if the notification could be created, false otherwise
     */
    abstract public function createUserNotification($aggregateMetricSpecDao, $userDao);

    /**
     * Delete a user notification tied to the aggregate metric spec.
     *
     * @param Tracker_AggregateMetricSpecDao $aggregateMetricSpecDao aggregateMetricSpec DAO
     * @param UserDao $userDao user DAO
     * @return bool true if the user and aggregate metric spec are valid and a
     * notification does not exist for this user and aggregate metric spec upon
     * returning, false otherwise
     */
    abstract public function deleteUserNotification($aggregateMetricSpecDao, $userDao);

    /**
     * Return a list of User Daos for all users with notifications on this aggregate metric spec.
     *
     * @param Tracker_AggregateMetricSpecDao $aggregateMetricSpecDao aggregateMetricSpec DAO
     * @return false|array of UserDao for all users with notification on the passed in $aggregateMetricSpecDao,
     * or false if the passed in spec is invalid
     */
    abstract public function getAllNotifiedUsers($aggregateMetricSpecDao);

    /**
     * Return a list of Jobs scheduled to notify users that the passed aggregate metric is above
     * the threshold defined in the passed aggregate metric spec.
     *
     * @param Tracker_AggregateMetricSpecDao $aggregateMetricSpecDao aggregateMetricSpec DAO
     * @param Tracker_AggregateMetricDao $aggregateMetricDao aggregateMetric DAO
     * @return false|array of Scheduler_JobDao for all users with a notification created, which will only
     * be populated if the aggregate metric is above the threshold defined on the aggregate metric spec and
     * there exist users to be notified on the aggregate metric spec, or false if the inputs are invalid.
     */
    abstract public function scheduleNotificationJobs($aggregateMetricSpecDao, $aggregateMetricDao);

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

    /**
     * Check whether the given policy is valid for the given aggregateMetricSpec and user.
     *
     * @param Tracker_AggregateMetricSpecDao $aggregateMetricSpecDao aggregateMetricSpec DAO
     * @param null|UserDao $userDao user DAO
     * @param int $policy policy
     * @return bool true if the given policy is valid for the given aggregateMetricSpec and user
     */
    public function policyCheck($aggregateMetricSpecDao, $userDao = null, $policy = MIDAS_POLICY_READ)
    {
        if (is_null($aggregateMetricSpecDao) || $aggregateMetricSpecDao === false) {
            return false;
        }

        /** @var Tracker_ProducerModel $producerModel */
        $producerModel = MidasLoader::loadModel('Producer', $this->moduleName);
        $producerDao = $aggregateMetricSpecDao->getProducer();

        return $producerModel->policyCheck($producerDao, $userDao, $policy);
    }
}
