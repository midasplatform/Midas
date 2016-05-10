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
 * Submission controller for the tracker module.
 *
 * @property Tracker_SubmissionModel $Tracker_Submission
 * @property Tracker_ProducerModel $Tracker_Producer
 */
class Tracker_SubmissionController extends Tracker_AppController
{
    /** @var array */
    public $_models = array('Community');

    /** @var array */
    public $_moduleModels = array('Producer', 'Submission');

    /**
     * Return a csv file holding the outputs of this submission.
     *
     * @param submissionUuid Uuid of the submission.  Read permission on the associated community required.
     * @param keyMetricsOnly (optional) Whether to return all metrics or key metrics only, defaults to true.
     * @param daysInterval (optional) If set, return all scalars from submissions with the same branch
     * and producer as the passed in submission, searching as far back as daysInterval before the
     * passed in submission.
     * @throws Zend_Exception
     */
    public function csvAction()
    {
        $this->disableLayout();
        $this->_helper->viewRenderer->setNeverRender();

        $submissionUuid = $this->getParam('submissionUuid');
        /** @var Tracker_SubmissionDao $submissionDao */
        $submissionDao = $this->Tracker_Submission->getSubmission($submissionUuid);
        if ($submissionDao === false) {
            throw new Zend_Exception('The submission does not exist', 403);
        }

        /** @var Tracker_ProducerDao $producerDao */
        $producerDao = $submissionDao->getProducer();
        if ($producerDao === false) {
            throw new Zend_Exception('The producer does not exist', 403);
        }

        /** @var CommunityDao $communityDao */
        $communityDao = $producerDao->getCommunity();
        if ($communityDao === false || $this->Community->policyCheck($communityDao, $this->userSession->Dao, MIDAS_POLICY_READ) === false
        ) {
            throw new Zend_Exception('The associated community does not exist or you do not READ access to the community', 403);
        }

        $keyMetrics = $this->getParam('keyMetricsOnly');
        $keyMetrics = $keyMetrics === 'false' ? false : true;
        $daysInterval = $this->getParam('daysInterval');
        $daysInterval = $daysInterval ? $daysInterval : false;
        $results = $this->Tracker_Submission->getTabularSubmissionDetails($producerDao, $submissionDao, $keyMetrics, $daysInterval);

        $output = fopen("php://output", 'w') || exit("Can't open php://output");
        $filename = 'producer_' . $submissionDao->getProducerId() . '_' . $submissionDao->getUuid() . '.csv';
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        /** @var array $resultsRow */
        foreach ($results as $resultsRow) {
            fputcsv($output, $resultsRow);
        }
        fclose($output) || exit("Can't close php://output");
    }
}
