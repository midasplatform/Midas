<?php
/*=========================================================================
 MIDAS Server
 Copyright (c) Kitware SAS. 26 rue Louis Guérin. 69100 Villeurbanne, FRANCE
 All rights reserved.
 More information http://www.kitware.com

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
 * Producer controller for the tracker module.
 *
 * @property Tracker_ProducerModel $Tracker_Producer
 * @property Tracker_TrendModel $Tracker_Trend
 * @package Modules\Tracker\Controller
 */
class Tracker_ProducerController extends Tracker_AppController
{
    /** @var array */
    public $_components = array('Breadcrumb');

    /** @var array */
    public $_models = array('Community');

    /** @var array */
    public $_moduleModels = array('Producer', 'Trend');

    /**
     * List all producers for a given community (in the tab). Requires read permission on community.
     *
     * Request parameters:
     *     communityId - The community id
     *
     * @throws Zend_Exception
     */
    public function listAction()
    {
        $this->disableLayout();

        /** @var int $commId */
        $commId = $this->getParam('communityId');
        if (!isset($commId)) {
            throw new Zend_Exception('Must pass communityId parameter');
        }

        /** @var CommunityDao $comm */
        $comm = $this->Community->load($commId);
        if (!$comm || !$this->Community->policyCheck($comm, $this->userSession->Dao, MIDAS_POLICY_READ)
        ) {
            throw new Zend_Exception('Read permission required on the community', 403);
        }
        $this->view->community = $comm;
        $this->view->producers = $this->Tracker_Producer->getByCommunityId($commId);
    }

    /**
     * View a producer, displaying all available trends.
     *
     * Request parameters:
     *     producerId - The id of the producer to display (requires community read permission)
     *
     * @throws Zend_Exception
     */
    public function viewAction()
    {
        /** @var int $producerId */
        $producerId = $this->getParam('producerId');
        if (!isset($producerId)) {
            throw new Zend_Exception('Must pass producerId parameter');
        }

        /** @var Tracker_ProducerDao $producer */
        $producer = $this->Tracker_Producer->load($producerId);
        $comm = $producer->getCommunity();
        if (!$producer || !$this->Community->policyCheck($comm, $this->userSession->Dao, MIDAS_POLICY_READ)
        ) {
            throw new Zend_Exception('Read permission required on the community', 403);
        }
        $this->view->producer = $producer;
        $this->view->trendGroups = $this->Tracker_Trend->getTrendsGroupByDatasets($producer);
        $this->view->isAdmin = $this->Community->policyCheck($comm, $this->userSession->Dao, MIDAS_POLICY_ADMIN);
        $this->view->json['tracker']['producer'] = $producer;

        $breadcrumbs = array(array('type' => 'community', 'object' => $comm, 'tab' => 'Trackers'));
        $breadcrumbs[] = array(
            'type' => 'custom',
            'text' => $producer->getDisplayName(),
            'icon' => $this->view->coreWebroot.'/public/images/icons/cog_go.png',
        );
        $this->Component->Breadcrumb->setBreadcrumbHeader($breadcrumbs, $this->view);
    }

    /**
     * Delete a producer, deleting all trend data within it (requires community admin).
     *
     * Request parameters:
     *     producerId - The id of the producer to delete
     *
     * @throws Zend_Exception
     */
    public function deleteAction()
    {
        $this->disableLayout();
        $this->disableView();

        /** @var int $producerId */
        $producerId = $this->getParam('producerId');
        if (!isset($producerId)) {
            throw new Zend_Exception('Must pass producerId parameter');
        }

        /** @var Tracker_ProducerDao $producer */
        $producer = $this->Tracker_Producer->load($producerId);
        $comm = $producer->getCommunity();
        if (!$producer || !$this->Community->policyCheck($comm, $this->userSession->Dao, MIDAS_POLICY_ADMIN)
        ) {
            throw new Zend_Exception('Admin permission required on the community', 403);
        }
        $this->Tracker_Producer->delete($producer);
    }

    /**
     * Show the dialog for editing the producer information.
     *
     * @throws Zend_Exception
     */
    public function editAction()
    {
        /** @var int $producerId */
        $producerId = $this->getParam('producerId');

        if (!isset($producerId)) {
            throw new Zend_Exception('Must pass producerId parameter');
        }

        /** @var Tracker_ProducerDao $producer */
        $producer = $this->Tracker_Producer->load($producerId);
        if (!$producer) {
            throw new Zend_Exception('Invalid producerId', 404);
        }
        if (!$this->Community->policyCheck($producer->getCommunity(), $this->userSession->Dao, MIDAS_POLICY_ADMIN)
        ) {
            throw new Zend_Exception('Admin permission required on the community', 403);
        }
        $this->disableLayout();
        $this->view->producer = $producer;
    }

    /**
     * Handle edit form submission.
     *
     * Request parameters:
     *     producerId - The id of the producer to edit
     *
     * @throws Zend_Exception
     */
    public function editsubmitAction()
    {
        $this->disableLayout();
        $this->disableView();

        /** @var int $producerId */
        $producerId = $this->getParam('producerId');

        if (!isset($producerId)) {
            throw new Zend_Exception('Must pass producerId parameter');
        }

        /** @var Tracker_ProducerDao $producer */
        $producer = $this->Tracker_Producer->load($producerId);
        if (!$this->Community->policyCheck($producer->getCommunity(), $this->userSession->Dao, MIDAS_POLICY_ADMIN)
        ) {
            throw new Zend_Exception('Admin permission required on the community', 403);
        }
        $displayName = $this->getParam('displayName');
        $description = $this->getParam('description');
        $repository = $this->getParam('repository');
        $revisionUrl = $this->getParam('revisionUrl');
        $executableName = $this->getParam('executableName');

        if (isset($displayName)) {
            $producer->setDisplayName($displayName);
        }
        if (isset($description)) {
            $producer->setDescription($description);
        }
        if (isset($repository)) {
            $producer->setRepository($repository);
        }
        if (isset($executableName)) {
            $producer->setExecutableName($executableName);
        }
        if (isset($revisionUrl)) {
            $producer->setRevisionUrl($revisionUrl);
        }
        $this->Tracker_Producer->save($producer);
        echo JsonComponent::encode(array('status' => 'ok', 'message' => 'Changes saved', 'producer' => $producer));
    }
}
