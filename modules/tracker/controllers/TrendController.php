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
/** Trend controller*/
class Tracker_TrendController extends Tracker_AppController
{
  public $_models = array('Community');
  public $_moduleModels = array('Producer', 'Trend');

  /**
   * View a given trend
   * @param trendId The id of the trend to view
   */
  public function viewAction()
    {
    $trendId = $this->_getParam('trendId');
    $startDate = $this->_getParam('startDate');
    $endDate = $this->_getParam('endDate');
    if(!isset($trendId))
      {
      throw new Zend_Exception('Must pass trendId parameter');
      }
    $trend = $this->Tracker_Trend->load($trendId);
    $comm = $trend->getProducer()->getCommunity();
    if(!$this->Community->policyCheck($comm, $this->userSession->Dao, MIDAS_POLICY_READ))
      {
      throw new Zend_Exception('Read permission required on the community', 403);
      }
    $this->view->trend = $trend;
    $this->view->isAdmin = $this->Community->policyCheck($comm, $this->userSession->Dao, MIDAS_POLICY_ADMIN);
    $header = '<ul class="pathBrowser">';
    $header .= '<li class="pathFolder"><img alt="" src="'.$this->view->coreWebroot.'/public/images/icons/community.png" /><span><a href="'.$this->view->webroot.'/community/'.$comm->getKey().'#Trackers">'.$comm->getName().'</a></span></li>';
    $header .= '<li class="pathFolder"><img alt="" src="'.$this->view->coreWebroot.'/public/images/icons/cog_go.png" /><span><a href="'.$this->view->webroot.'/tracker/producer/view?producerId='.$trend->getProducer()->getKey().'">'.$trend->getProducer()->getDisplayName().'</a></span></li>';
    $header .= '<li class="pathFolder"><img alt="" src="'.$this->view->moduleWebroot.'/public/images/chart_line.png" /><span>'.$trend->getDisplayName().'</span></li>';
    $header .= '</ul>';
    $this->view->header = $header;

    $this->view->json['tracker']['scalars'] = $this->Tracker_Trend->getScalars($trend, $startDate, $endDate);
    $this->view->json['tracker']['trend'] = $trend;
    }

  /**
   * Delete a trend, deleting all scalar records within it (requires community admin)
   */
  public function deleteAction()
    {
    // TODO (include progress reporting)
    }
}//end class
