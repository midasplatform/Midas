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
/** Scalar controller*/
class Tracker_ScalarController extends Tracker_AppController
  {
  public $_models = array('Community');
  public $_moduleModels = array('Scalar', 'Trend');

  /**
   * Display the dialog of scalar details, including associated result items with thumbnails
   * @param scalarId The id of the scalar
   */
  public function detailsAction()
    {
    $this->disableLayout();
    $scalarId = $this->_getParam('scalarId');
    if(!isset($scalarId))
      {
      throw new Zend_Exception('Must set scalarId parameter');
      }
    $scalar = $this->Tracker_Scalar->load($scalarId);
    if(!$scalar)
      {
      throw new Zend_Exception('Scalar with that id does not exist', 404);
      }
    $producer = $scalar->getTrend()->getProducer();
    $comm = $producer->getCommunity();
    if(!$this->Community->policyCheck($comm, $this->userSession->Dao, MIDAS_POLICY_READ))
      {
      throw new Zend_Exception('Permission denied', 403);
      }
    $this->view->isAdmin = $this->Community->policyCheck($comm, $this->userSession->Dao, MIDAS_POLICY_ADMIN);
    $this->view->scalar = $scalar;
    $rev = $scalar->getProducerRevision();
    $repoBrowserUrl = $producer->getRevisionUrl();
    if($repoBrowserUrl)
      {
      $repoBrowserUrl = preg_replace('/%revision/', $rev, $repoBrowserUrl);
      $this->view->revisionHtml = '<a target="_blank" href="'.$repoBrowserUrl.'">'.$rev.'</a>';
      }
    else
      {
      $this->view->revisionHtml = $rev;
      }
    $this->view->resultItems = $this->Tracker_Scalar->getAssociatedItems($scalar);
    $this->view->otherValues = $this->Tracker_Scalar->getOtherValuesFromSubmission($scalar);

    if($scalar->getUserId() != -1)
      {
      $this->view->submittedBy = $scalar->getUser();
      }
    else
      {
      $this->view->submittedBy = null;
      }
    }

  /**
   * Delete a scalar value (requires community admin)
   * @param scalarId
   */
  public function deleteAction()
    {
    $this->disableLayout();
    $this->disableView();
    $scalarId = $this->_getParam('scalarId');
    if(!isset($scalarId))
      {
      throw new Zend_Exception('Must set scalarId parameter');
      }
    $scalar = $this->Tracker_Scalar->load($scalarId);
    if(!$scalar)
      {
      throw new Zend_Exception('Scalar with that id does not exist', 404);
      }
    $comm = $scalar->getTrend()->getProducer()->getCommunity();
    if(!$this->Community->policyCheck($comm, $this->userSession->Dao, MIDAS_POLICY_ADMIN))
      {
      throw new Zend_Exception('Permission denied', 403);
      }
    $this->Tracker_Scalar->delete($scalar);
    echo JsonComponent::encode(array('status' => 'ok', 'message' => 'Scalar deleted'));
    }
  } // end class
