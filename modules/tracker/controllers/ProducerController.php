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
  public $_models = array('Community');
  public $_moduleModels = array('Producer');

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
    $this->view->trends = $producer->getTrends();
    $this->view->isAdmin = $this->Community->policyCheck($comm, $this->userSession->Dao, MIDAS_POLICY_ADMIN);
    $this->view->json['tracker']['producer'] = $producer;

    $header = '<ul class="pathBrowser">';
    $header .= '<li class="pathFolder"><img alt="" src="'.$this->view->coreWebroot.'/public/images/icons/community.png" /><span><a href="'.$this->view->webroot.'/community/'.$comm->getKey().'#Trackers">'.$comm->getName().'</a></span></li>';
    $header .= '<li class="pathFolder"><img alt="" src="'.$this->view->coreWebroot.'/public/images/icons/cog_go.png" /><span>'.$producer->getDisplayName().'</span></li>';
    $header .= '</ul>';
    $this->view->header = $header;
    }

  /**
   * Delete a producer, deleting all trend data within it (requires community admin)
   */
  public function deleteAction()
    {
    // TODO (include progress reporting)
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
    $this->Tracker_Producer->save($producer);
    echo JsonComponent::encode(array('status' => 'ok', 'message' => 'Changes saved', 'producer' => $producer));
    }
  
}//end class
