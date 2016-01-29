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
 * Submission base model class for the tracker module.
 */
abstract class Tracker_SubmissionModelBase extends Tracker_AppModel
{
    /** Constructor. */
    public function __construct()
    {
        parent::__construct();

        $this->_name = 'tracker_submission';
        $this->_key = 'submission_id';
        $this->_mainData = array(
            'submission_id' => array('type' => MIDAS_DATA),
            'producer_id' => array('type' => MIDAS_DATA),
            'name' => array('type' => MIDAS_DATA),
            'uuid' => array('type' => MIDAS_DATA),
            'submit_time' => array('type' => MIDAS_DATA),
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
     * Create a submission.
     *
     * @param Tracker_ProducerDao $producerDao the producer to which the submission was submitted
     * @param string $uuid the uuid of the submission
     * @param string $name the name of the submission (defaults to '')
     * @return Tracker_SubmissionDao
     */
    abstract public function createSubmission($producerDao, $uuid, $name = '');

    /**
     * Get a submission from its uuid.
     *
     * @param string $uuid the uuid of the submission
     * @return Tracker_SubmissionDao submission DAO
     */
    abstract public function getSubmission($uuid);

    /**
     * Return the submission with the given uuid (creating one if necessary).
     *
     * @param Tracker_ProducerDao $producerDao producer DAO
     * @param string $uuid the uuid of the submission
     * @return Tracker_SubmissionDao submission DAO
     */
    abstract public function getOrCreateSubmission($producerDao, $uuid);

    /**
     * Get submissions associated with a given producer.
     *
     * @param Tracker_ProducerDao $producerDao producer DAO
     * @return array submission DAOs
     */
    abstract public function getSubmissionsByProducer($producerDao);

    /**
     * Get the scalars associated with a submission.
     * @param Tracker_SubmissionDao $submissionDao submission DAO
     * @param bool $key whether to only retrieve scalars of key trends
     * @return array submission DAOs
     */
    abstract public function getScalars($submissionDao, $key = false);

    /**
     * Get submissions associated with a given producer.
     *
     * @param Tracker_ProducerDao $producerDao producer DAO
     * @param false | string $date the date in which to check
     * @param string $branch the branch of the submission for which to search
     * @param bool $onlyOneDay whether to only get the last day.
     * @return Tracker_SubmissionDao submission
     */
    abstract public function getLatestSubmissionByProducerDateAndBranch($producerDao,
                                                                        $date = false,
                                                                        $branch = 'master',
                                                                        $onlyOneDay = true);

    /**
     * Get trends associated with a submission.
     * @param Tracker_SubmissionDao $submissionDao submission DAO
     * @param bool $key whether to only retrieve key trends
     * @return array trend DAOs
     */
    abstract public function getTrends($submissionDao, $key = true);
}
