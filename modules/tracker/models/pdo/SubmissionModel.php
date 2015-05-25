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
 *
 * @package Modules\Tracker\Model
 */
class Tracker_SubmissionModel extends Tracker_SubmissionModelBase
{

    /**
     * Create a submission based on a uuid
     *
     * @param string $uuid
     * @param null|int $userId user id
     * @param string $name defaults to empty
     * @return submission DAO
     */
    public function createSubmission($uuid, $userId, $name = '')
    {
        $data = array('uuid' => $uuid,
                      'name' => $name,
                      'user_id' => $userId);
        $this->database->getDB()->insert('tracker_submission', $data);
    }

    /**
     * Return the scalars for a given submission
     *
     * @param Tracker_SubmissionDao $submissionDao submission DAO
     * @param null|int $userId user id
     * @return array scalar DAOs
     */
    public function getScalars($submissionDao, $userId)
    {
        $sql = $this->database->select()->setIntegrityCheck(false)
             ->from('tracker_scalar')
             ->where('submission_id = ?', $submissionDao->getKey());

        $scalarDaos = array();
        $rows = $this->database->fetchAll($sql);

        /** @var Zend_Db_Table_Row_Abstract $row */
        foreach ($rows as $row) {
            $scalarDaos[] = $this->initDao('Scalar', $row, $this->moduleName);
        }

        return $scalarDaos;
    }

}