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

/** Upgrade the tracker module to version 1.3.0. */
class Tracker_Upgrade_1_3_0 extends MIDASUpgrade
{
    /** Upgrade a MySQL database. */
    public function mysql()
    {
        // Add submission_id to the param table
        $this->migrateAllScalarsToSubmissions();
        // remove scalar_id from the param table

    }

    /** Upgrade a PostgreSQL database. */
    public function pgsql()
    {
    }

    /** Group all scalars into submissions using the same logic on the scalar details page. */
    private function migrateAllScalarsToSubmissions()
    {
        $logger = Zend_Registry::get('logger');
        $logger->debug('migrateScalarParams');

        /** @var Tracker_ScalarModel $scalarModel */
        $scalarModel = MidasLoader::loadModel('Scalar', $this->moduleName);
        /** @var Tracker_SubmissionModel $submissionModel */
        $submissionModel = MidasLoader::loadModel('Submission', $this->moduleName);

        /** @var Tracker_ParamModel $paramModel */
        $paramModel = MidasLoader::loadModel('Param', $this->moduleName);


        // Roll all scalars into submissions
        $query_result = $this->db->query('SELECT * FROM tracker_scalar WHERE submission_id=-1;');
        if ($query_result !== false) {
            while ($row = $query_result->fetch(PDO::FETCH_ASSOC)) {

                /** @var Tracker_ScalarDao $scalarDao */
                $scalarDao = $scalarModel->initDao('scalar', $row);
                $logger->debug($scalarDao);

                /** @var Tracker_SubmissionDao $submissionDao */
                $submissionDao = MidasLoader::newDao('SubmissionDao', $this->moduleName);

                /** @var UuidComponent $uuidComponent */
                $uuidComponent = MidasLoader::loadComponent('UuidComponent');
                $uuid = $uuidComponent->generate();
                $submissionDao->setUuid($uuid);

                $submissionDao = $submissionModel->save($submissionDao);
                $submissionId = $submissionDao->getKey();
                $scalarDao->setSubmissionId($submissionId);
                $scalarModel->save($scalarDao);

                foreach ($scalarModel->getOtherScalarsFromSubmissionLegacy($scalarDao) as $scalarDao) {
                    if ($scalarDao->getSubmissionId() === -1) {
                        $scalarDao->setSubmissionId($submissionId);
                        $scalarModel->save($scalarDao);
                    }
                }
            }
        }

        // Roll certain scalar values onto submissions
        $query_result = $this->db->query('SELECT * FROM tracker_submission');
        if ($query_result !== false) {
            while ($row = $query_result->fetch(PDO::FETCH_ASSOC)) {

                $submissionDao = $submissionModel->initDao('submission', $row);
                $scalarDaos = $submissionDao->getScalars();
                if (count($scalarDaos) > 0) {
                    $scalarDao = $scalarDaos[0];
                    $submissionDao->set
                }
            }
        }


        // Roll params onto submissions
        $query_result = $this->db->query('SELECT * FROM tracker_param as tp, tracker_scalar as ts where tp.scalar_id=ts.scalar_id');
        if($query_result !== false) {
            while ($row = $query_result->fetch(PDO::FETCH_ASSOC)) {
                /** @var Tracker_ParamDao $paramDao */
                $paramDao = $paramModel->initDao('param', $row);
                $logger->debug($paramDao);
                $paramDao->setSubmissionId($row['submission_id']);
                $paramModel->save($paramDao);
            }
        }

        // Delete duplicate params
        $query_result = $this->db->query('SELECT * FROM tracker_param');
        if($query_result !== false) {
            while ($row = $query_result->fetch(PDO::FETCH_ASSOC)) {


    }
}
