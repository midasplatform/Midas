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

require_once BASE_PATH.'/modules/tracker/models/base/SubmissionModelBase.php';

/**
 * Submission model for the tracker module.
 */
class Tracker_SubmissionModel extends Tracker_SubmissionModelBase
{
    /**
     * Create a submission.
     *
     * @param Tracker_ProducerDao $producerDao the producer to which the submission was submitted
     * @param string $uuid the uuid of the submission
     * @param string $name the name of the submission (defaults to '')
     * @return void
     */
    public function createSubmission($producerDao, $uuid, $name = '')
    {
        $data = array(
            'producer_id' => $producerDao->getKey(),
            'uuid' => $uuid,
            'name' => $name,
        );
        $this->database->getDB()->insert('tracker_submission', $data);
    }

    /**
     * Return the scalars for a given submission.
     *
     * @param Tracker_SubmissionDao $submissionDao submission DAO
     * @param bool $key whether to only retrieve scalars of key trends
     * @return array scalar DAOs
     */
    public function getScalars($submissionDao, $key=false)
    {
        if ($key) {
            $sql = $this->database->select()->setIntegrityCheck(false)->from('tracker_scalar')->join(
                'tracker_trend',
                'tracker_scalar.trend_id = tracker_trend.trend_id',
                array()
            )->where('submission_id = ?', $submissionDao->getKey()
            )->where('key_metric = ?', 1);
        } else {
            $sql = $this->database->select()->setIntegrityCheck(false)->from('tracker_scalar')
                ->where('submission_id = ?', $submissionDao->getKey());
        }

        $scalarDaos = array();
        $rows = $this->database->fetchAll($sql);

        /** @var Zend_Db_Table_Row_Abstract $row */
        foreach ($rows as $row) {
            $scalarDaos[] = $this->initDao('Scalar', $row, $this->moduleName);
        }

        return $scalarDaos;
    }

    /**
     * Get submissions associated with a given producer.
     *
     * @param Tracker_ProducerDao $producerDao producer DAO
     * @return array submission DAOs
     */
    public function getSubmissionsByProducer($producerDao)
    {
        $sql = $this->database->select()->setIntegrityCheck(false)
            ->from('tracker_submission')
            ->where('producer_id = ?', $producerDao->getKey());
        $submissionDaos = array();

        $rows = $this->database->fetchAll($sql);
        /** @var Zend_Db_Table_Row_Abstract $row */
        foreach ($rows as $row) {
            $submissionDaos[] = $this->initDao('Submission', $row, $this->moduleName);
        }

        return $submissionDaos;
    }

    /**
     * Return the submission with the given UUID.
     *
     * @param string $uuid the uuid of the submission
     * @return false|Tracker_SubmissionDao submission Dao
     */
    public function getSubmission($uuid)
    {
        $sql = $this->database->select()->setIntegrityCheck(false)
            ->from('tracker_submission')
            ->where('uuid = ?', $uuid);
        $res = $this->database->fetchAll($sql);
        if ($res->count() === 0) {
            return false;
        } else {
            $submissionDao = $this->initDao('Submission', $res[0], $this->moduleName);

            return $submissionDao;
        }
    }

    /**
     * Return the submission with the given UUID (creating one if necessary).
     *
     * @param Tracker_ProducerDao $producerDao the producer
     * @param string $uuid the uuid of the submission
     * @return Tracker_SubmissionDao
     */
    public function getOrCreateSubmission($producerDao, $uuid)
    {
        $sql = $this->database->select()->setIntegrityCheck(false)
            ->from('tracker_submission')
            ->where('uuid = ?', $uuid)
            ->where('producer_id = ?', $producerDao->getKey());
        $res = $this->database->fetchAll($sql);
        if (count($res) === 1) {
            $submissionDao = $this->initDao('Submission', $res[0],
                                            $this->moduleName);
        } else {
            $doc = array();
            $doc['uuid'] = $uuid;
            $doc['producer_id'] = $producerDao->getKey();

            /** @var Tracker_SubmissionDao $submissionDao */
            $submissionDao = $this->initDao('Submission', $doc,
                                            $this->moduleName);
            $this->save($submissionDao);
            $submissionId = $submissionDao->getSubmissionId();
            $submissionDao = $this->load($submissionId);
        }

        return $submissionDao;
    }
}
