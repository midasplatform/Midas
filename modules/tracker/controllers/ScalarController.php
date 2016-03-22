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
 * Scalar controller for the tracker module.
 *
 * @property Tracker_ScalarModel $Tracker_Scalar
 * @property Tracker_SubmissionModel $Tracker_Submission
 */
class Tracker_ScalarController extends Tracker_AppController
{
    /** @var array */
    public $_moduleModels = array('Scalar', 'Submission');

    /**
     * Display the dialog of scalar details, including associated result items with thumbnails.
     *
     * Request parameters:
     *     scalarId - The id of the scalar
     *
     * @throws Zend_Exception
     */
    public function detailsAction()
    {
        $this->disableLayout();

        /** @var int $scalarId */
        $scalarId = $this->getParam('scalarId');

        if (!isset($scalarId)) {
            throw new Zend_Exception('The required scalarId parameter is missing');
        }

        /** @var Tracker_ScalarDao $scalarDao */
        $scalarDao = $this->Tracker_Scalar->load($scalarId);

        /** @var Tracker_SubmissionDao $submissionDao */
        $submissionDao = $this->Tracker_Submission->load($scalarDao->getSubmissionId());

        if ($this->Tracker_Scalar->policyCheck($scalarDao, $this->userSession->Dao, MIDAS_POLICY_READ) === false
        ) {
            throw new Zend_Exception('The scalar does not exist or you do not have the necessary permission', 403);
        }

        $this->view->isAdmin = $this->Tracker_Scalar->policyCheck($scalarDao, $this->userSession->Dao, MIDAS_POLICY_ADMIN);

        $this->view->scalar = $scalarDao;
        $this->view->submission = $submissionDao;
        $this->view->extraParams = $submissionDao->getParams();
        $this->view->extraUrls = json_decode($submissionDao->getExtraUrls(), true);

        $revisionUrl = $submissionDao->getProducer()->getRevisionUrl();
        $producerRevision = $submissionDao->getProducerRevision();

        if (!is_null($revisionUrl)) {
            $producerRevisionUrl = preg_replace('/%revision/', $producerRevision, $revisionUrl);
            $this->view->revisionHtml = '<a target="_blank" href="'.$producerRevisionUrl.'">'.$producerRevision.'</a>';
        } else {
            $this->view->revisionHtml = $producerRevision;
        }

        $this->view->resultItems = $this->Tracker_Submission->getAssociatedItems($submissionDao);
        $this->view->otherValues = $this->Tracker_Submission->getValuesFromSubmission($submissionDao);

        if ($submissionDao->getUserId() !== -1) {
            $this->view->submittedBy = $submissionDao->getUser();
        } else {
            $this->view->submittedBy = null;
        }
    }

    /**
     * Delete a scalar value (requires community admin).
     *
     * Request parameters:
     *     scalarId - The id of the scalar
     *
     * @throws Zend_Exception
     */
    public function deleteAction()
    {
        $this->disableLayout();
        $this->disableView();

        /** @var int $scalarId */
        $scalarId = $this->getParam('scalarId');

        if (!isset($scalarId)) {
            throw new Zend_Exception('The required scalarId parameter is missing');
        }

        /** @var Tracker_ScalarDao $scalarDao */
        $scalarDao = $this->Tracker_Scalar->load($scalarId);

        if ($this->Tracker_Scalar->policyCheck($scalarDao, $this->userSession->Dao, MIDAS_POLICY_READ) === false
        ) {
            throw new Zend_Exception('The scalar does not exist or you do not have the necessary permission', 403);
        }

        $this->Tracker_Scalar->delete($scalarDao);
        echo JsonComponent::encode(array('status' => 'ok', 'message' => 'Scalar deleted'));
    }
}
