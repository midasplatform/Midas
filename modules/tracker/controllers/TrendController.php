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
    $header = '<img style="position: relative; top: 3px;" alt="" src="'.$this->view->moduleWebroot.'/public/images/chart_line.png" />';
    $header .= ' <a href="'.$this->view->webroot.'/'.$this->moduleName.'/producer/view?producerId='.$trend->getProducer()->getKey();
    $header .= '">'.$trend->getProducer()->getDisplayName().'</a>: '.$trend->getDisplayName();
    $this->view->header = $header;

    $this->view->json['tracker']['scalars'] = $this->Tracker_Trend->getScalars($trend);
    }

  /**
   * Delete a trend, deleting all scalar records within it (requires community admin)
   */
  public function deleteAction()
    {
    // TODO (include progress reporting)
    }
}//end class
