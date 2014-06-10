<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/
/** Producer controller*/
class Tracker_ProducerController extends Tracker_AppController
  {
  public $_components = array('Breadcrumb');
  public $_models = array('Community');
  public $_moduleModels = array('Producer', 'Trend');

  /**
   * List all producers for a given community (in the tab). Requires read permission on community.
   * @param communityId The community id
   */
  public function listAction()
    {
    $this->disableLayout();
    $commId = $this->_getParam('communityId');
    if(!isset($commId))
      {
      throw new Zend_Exception('Must pass communityId parameter');
      }
    $comm = $this->Community->load($commId);
    if(!$comm || !$this->Community->policyCheck($comm, $this->userSession->Dao, MIDAS_POLICY_READ))
      {
      throw new Zend_Exception('Read permission required on the community', 403);
      }
    $this->view->community = $comm;
    $this->view->producers = $this->Tracker_Producer->getByCommunityId($commId);
    }

  /**
   * View a producer, displaying all available trends
   * @param producerId The id of the producer to display (requires community read permission)
   */
  public function viewAction()
    {
    $producerId = $this->_getParam('producerId');
    if(!isset($producerId))
      {
      throw new Zend_Exception('Must pass producerId parameter');
      }
    $producer = $this->Tracker_Producer->load($producerId);
    $comm = $producer->getCommunity();
    if(!$producer || !$this->Community->policyCheck($comm, $this->userSession->Dao, MIDAS_POLICY_READ))
      {
      throw new Zend_Exception('Read permission required on the community', 403);
      }
    $this->view->producer = $producer;
    $this->view->trendGroups = $this->Tracker_Trend->getTrendsGroupByDatasets($producer);
    $this->view->isAdmin = $this->Community->policyCheck($comm, $this->userSession->Dao, MIDAS_POLICY_ADMIN);
    $this->view->json['tracker']['producer'] = $producer;

    $breadcrumbs = array(array('type' => 'community',
                               'object' => $comm,
                               'tab' => 'Trackers'));
    $breadcrumbs[] = array('type' => 'custom',
                           'text' => $producer->getDisplayName(),
                           'icon' => $this->view->coreWebroot.'/public/images/icons/cog_go.png');
    $this->Component->Breadcrumb->setBreadcrumbHeader($breadcrumbs, $this->view);
    }

  /**
   * Delete a producer, deleting all trend data within it (requires community admin)
   * @param producerId The id of the producer to delete
   */
  public function deleteAction()
    {
    $this->disableLayout();
    $this->disableView();
    $producerId = $this->_getParam('producerId');
    if(!isset($producerId))
      {
      throw new Zend_Exception('Must pass producerId parameter');
      }
    $producer = $this->Tracker_Producer->load($producerId);
    $comm = $producer->getCommunity();
    if(!$producer || !$this->Community->policyCheck($comm, $this->userSession->Dao, MIDAS_POLICY_ADMIN))
      {
      throw new Zend_Exception('Admin permission required on the community', 403);
      }
    $this->Tracker_Producer->delete($producer);
    }

  /**
   * Show the dialog for editing the producer information
   */
  public function editAction()
    {
    $producerId = $this->_getParam('producerId');

    if(!isset($producerId))
      {
      throw new Zend_Exception('Must pass producerId parameter');
      }
    $producer = $this->Tracker_Producer->load($producerId);
    if(!$producer)
      {
      throw new Zend_Exception('Invalid producerId', 404);
      }
    if(!$this->Community->policyCheck($producer->getCommunity(), $this->userSession->Dao, MIDAS_POLICY_ADMIN))
      {
      throw new Zend_Exception('Admin permission required on the community', 403);
      }
    $this->disableLayout();
    $this->view->producer = $producer;
    }

  /**
   * Handle edit form submission
   * @param producerId The id of the producer to edit
   */
  public function editsubmitAction()
    {
    $this->disableLayout();
    $this->disableView();
    $producerId = $this->_getParam('producerId');

    if(!isset($producerId))
      {
      throw new Zend_Exception('Must pass producerId parameter');
      }
    $producer = $this->Tracker_Producer->load($producerId);
    if(!$this->Community->policyCheck($producer->getCommunity(), $this->userSession->Dao, MIDAS_POLICY_ADMIN))
      {
      throw new Zend_Exception('Admin permission required on the community', 403);
      }
    $displayName = $this->_getParam('displayName');
    $description = $this->_getParam('description');
    $repository = $this->_getParam('repository');
    $revisionUrl = $this->_getParam('revisionUrl');
    $executableName = $this->_getParam('executableName');

    if(isset($displayName))
      {
      $producer->setDisplayName($displayName);
      }
    if(isset($description))
      {
      $producer->setDescription(UtilityComponent::filterHtmlTags($description));
      }
    if(isset($repository))
      {
      $producer->setRepository($repository);
      }
    if(isset($executableName))
      {
      $producer->setExecutableName($executableName);
      }
    if(isset($revisionUrl))
      {
      $producer->setRevisionUrl($revisionUrl);
      }
    $this->Tracker_Producer->save($producer);
    echo JsonComponent::encode(array('status' => 'ok', 'message' => 'Changes saved', 'producer' => $producer));
    }

  } // end class
