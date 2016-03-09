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
 * Producer controller for the tracker module.
 *
 * @property Tracker_ProducerModel $Tracker_Producer
 * @property Tracker_TrendModel $Tracker_Trend
 */
class Tracker_ProducerController extends Tracker_AppController
{
    /** @var array */
    public $_components = array('Breadcrumb');

    /** @var array */
    public $_models = array('Community');

    /** @var array */
    public $_moduleModels = array('AggregateMetric', 'AggregateMetricSpec', 'Producer', 'Trend');

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

        /** @var int $communityId */
        $communityId = $this->getParam('communityId');

        if (!isset($communityId)) {
            throw new Zend_Exception('The required communityId parameter is missing');
        }

        /** @var CommunityDao $communityDao */
        $communityDao = $this->Community->load($communityId);

        if ($communityDao === false || $this->Community->policyCheck($communityDao, $this->userSession->Dao, MIDAS_POLICY_READ) === false
        ) {
            throw new Zend_Exception('The community does not exist or you do not have the necessary permission', 403);
        }

        $this->view->community = $communityDao;
        $this->view->producers = $this->Tracker_Producer->getByCommunityId($communityId);
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
            throw new Zend_Exception('The required producerId parameter is missing');
        }

        /** @var Tracker_ProducerDao $producerDao */
        $producerDao = $this->Tracker_Producer->load($producerId);

        if ($this->Tracker_Producer->policyCheck($producerDao, $this->userSession->Dao, MIDAS_POLICY_READ) === false
        ) {
            throw new Zend_Exception('The producer does not exist or you do not have the necessary permission on its community', 403);
        }

        $this->view->producer = $producerDao;
        $this->view->trendGroups = $this->Tracker_Trend->getTrendsGroupByDatasets($producerDao);
        $this->view->isAdmin = $this->Tracker_Producer->policyCheck($producerDao, $this->userSession->Dao, MIDAS_POLICY_ADMIN);
        $this->view->json['tracker']['producer'] = $producerDao;

        $breadcrumbs = array(
            array(
                'type' => 'community',
                'object' => $producerDao->getCommunity(),
                'tab' => 'Trackers',
            ),
            array(
                'type' => 'custom',
                'text' => $producerDao->getDisplayName(),
                'icon' => $this->view->baseUrl('core/public/images/icons/cog_go.png'),
            ),

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
            throw new Zend_Exception('The required producerId parameter is missing');
        }

        /** @var Tracker_ProducerDao $producerDao */
        $producerDao = $this->Tracker_Producer->load($producerId);

        if ($this->Tracker_Producer->policyCheck($producerDao, $this->userSession->Dao, MIDAS_POLICY_ADMIN) === false
        ) {
            throw new Zend_Exception('The producer does not exist or you do not have the necessary permission on its community', 403);
        }

        $this->Tracker_Producer->delete($producerDao);
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
            throw new Zend_Exception('The required producerId parameter is missing');
        }

        /** @var Tracker_ProducerDao $producerDao */
        $producerDao = $this->Tracker_Producer->load($producerId);

        if ($this->Tracker_Producer->policyCheck($producerDao, $this->userSession->Dao, MIDAS_POLICY_ADMIN) === false
        ) {
            throw new Zend_Exception('The producer does not exist or you do not have the necessary permission on its community', 403);
        }

        $this->disableLayout();
        $this->view->producer = $producerDao;
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

        /** @var Tracker_ProducerDao $producerDao */
        $producerDao = $this->Tracker_Producer->load($producerId);

        if ($this->Tracker_Producer->policyCheck($producerDao, $this->userSession->Dao, MIDAS_POLICY_ADMIN) === false
        ) {
            throw new Zend_Exception('The producer does not exist or you do not have the necessary permission on its community', 403);
        }

        /** @var string $displayName */
        $displayName = $this->getParam('displayName');

        if (isset($displayName)) {
            $producerDao->setDisplayName($displayName);
        }

        /** @var string $description */
        $description = $this->getParam('description');

        if (isset($description)) {
            $producerDao->setDescription($description);
        }

        /** @var string $repository */
        $repository = $this->getParam('repository');

        if (isset($repository)) {
            $producerDao->setRepository($repository);
        }

        /** @var string $revisionUrl */
        $revisionUrl = $this->getParam('revisionUrl');

        if (isset($revisionUrl)) {
            $producerDao->setRevisionUrl($revisionUrl);
        }

        /** @var string $executableName */
        $executableName = $this->getParam('executableName');

        if (isset($executableName)) {
            $producerDao->setExecutableName($executableName);
        }

        $this->Tracker_Producer->save($producerDao);
        echo JsonComponent::encode(array('status' => 'ok', 'message' => 'Changes saved', 'producer' => $producerDao));
    }

    /**
     * Dialog for create/edit/view/deleting an aggregate metric for this producer.
     *
     * @param producerId Id of the producer.  Admin permission on the associated community required.
     * @throws Zend_Exception
     */
    public function aggregatemetricAction()
    {
        $this->disableLayout();

        $producerId = $this->getParam('producerId');
        /** @var Tracker_ProducerDao $producerDao */
        $producerDao = $this->Tracker_Producer->load($producerId);
        /** @var CommunityDao $communityDao */
        $communityDao = $producerDao->getCommunity();

        if ($communityDao === false || $this->Community->policyCheck($communityDao, $this->userSession->Dao, MIDAS_POLICY_ADMIN) === false
        ) {
            throw new Zend_Exception('The associated community does not exist or you do not Admin access to the community', 403);
        }

        $producerParams = array(
            'producer_id' => $producerDao->getProducerId(),
            'key_metric' => 1,
        );
        $distinctTrendNames = array();
        /** @var array $producerTrends */
        $producerTrends = $this->Tracker_Trend->getAllByParams($producerParams);
        /** @var Tracker_TrendDao $trendDao */
        foreach ($producerTrends as $trendDao) {
            /** @var string $trendMetricName */
            $trendMetricName = $trendDao->getMetricName();
            if (!in_array($trendMetricName, $distinctTrendNames)) {
                $distinctTrendNames[] = $trendMetricName;
            }
        }

        /** @var array $aggregateMetricSpecs */
        $aggregateMetricSpecs = $this->Tracker_AggregateMetricSpec->getAggregateMetricSpecsForProducer($producerDao);
        $this->view->producer = $producerDao;
        $this->view->distinctTrendNames = $distinctTrendNames;
        $this->view->trackerJson = json_encode(array('aggregateMetricSpecs' => $aggregateMetricSpecs));
    }
}
