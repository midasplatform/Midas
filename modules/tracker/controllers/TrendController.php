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
  public $_moduleModels = array('Producer', 'ThresholdNotification', 'Trend');

  /**
   * View a given trend
   * @param trendId Comma separated list of trends to show using the left Y axis
   * @param rightTrendId (optional) The id of the trend to display on the right Y axis
   * @param startDate (optional) The start date to retrieve scalars
   * @param endDate (optional) The end date to retrieve scalars
   * @param yMin (optional) Minimum value of the left y axis
   * @param yMax (optional) Maximum value of the left y axis
   * @param y2Min (optional) Minimum value of the right y axis
   * @param y2Max (optional) Maximum value of the right y axis
   */
  public function viewAction()
    {
    $trendId = $this->_getParam('trendId');
    $startDate = $this->_getParam('startDate');
    $endDate = $this->_getParam('endDate');
    $rightTrendId = $this->_getParam('rightTrendId');
    $yMin = $this->_getParam('yMin');
    $yMax = $this->_getParam('yMax');
    $y2Min = $this->_getParam('y2Min');
    $y2Max = $this->_getParam('y2Max');

    if(!isset($trendId))
      {
      throw new Zend_Exception('Must pass trendId parameter');
      }
    // Provide sensible default date range
    if(!isset($startDate))
      {
      $startDate = strtotime('-1 month');
      }
    else
      {
      $startDate = strtotime($startDate);
      }
    if(!isset($endDate))
      {
      $endDate = time();
      }
    else
      {
      $endDate = strtotime($endDate.' 23:59:59');
      }
    $startDate = date('Y-m-d H:i:s', $startDate);
    $endDate = date('Y-m-d H:i:s', $endDate);

    $trendIds = explode(' ', trim(str_replace(',', ' ', $trendId)));
    $this->view->trends = array();
    foreach($trendIds as $trendId)
      {
      $trend = $this->Tracker_Trend->load($trendId);
      $comm = $trend->getProducer()->getCommunity();
      if(!$this->Community->policyCheck($comm, $this->userSession->Dao, MIDAS_POLICY_READ))
        {
        throw new Zend_Exception('Read permission required on the community', 403);
        }
      $this->view->json['tracker']['scalars'][] = $this->Tracker_Trend->getScalars($trend, $startDate, $endDate);
      $this->view->json['tracker']['trends'][] = $trend;
      $this->view->trends[] = $trend;
      }
    if(isset($rightTrendId))
      {
      $rightTrend = $this->Tracker_Trend->load($rightTrendId);
      if($comm->getKey() != $rightTrend->getProducer()->getCommunityId())
        {
        throw new Zend_Exception('Right trend must belong to the same community', 403);
        }
      $this->view->rightTrend = $rightTrend;
      }
    $this->view->isAdmin = $this->Community->policyCheck($comm, $this->userSession->Dao, MIDAS_POLICY_ADMIN);
    $this->view->logged = $this->logged;
    $header = '<ul class="pathBrowser">';
    $header .= '<li class="pathFolder"><img alt="" src="'.$this->view->coreWebroot.'/public/images/icons/community.png" /><span><a href="'.$this->view->webroot.'/community/'.$comm->getKey().'#Trackers">'.$comm->getName().'</a></span></li>';
    $header .= '<li class="pathFolder"><img alt="" src="'.$this->view->coreWebroot.'/public/images/icons/cog_go.png" /><span><a href="'.$this->view->webroot.'/tracker/producer/view?producerId='.$trend->getProducer()->getKey().'">'.$trend->getProducer()->getDisplayName().'</a></span></li>';
    $header .= '<li class="pathFolder"><img alt="" src="'.$this->view->moduleWebroot.'/public/images/chart_line.png" /><span>';
    if(count($this->view->trends) == 1)
      {
      $header .= $this->view->trends[0]->getDisplayName();
      }
    else
      {
      $header .= count($this->view->trends).' trends';
      }
    if($this->view->rightTrend)
      {
      $header .= ' &amp; '.$rightTrend->getDisplayName();
      }
    $header .= '</span></li>';
    $header .= '</ul>';
    $this->view->header = $header;

    if(isset($rightTrend))
      {
      $this->view->json['tracker']['rightTrend'] = $rightTrend;
      $this->view->json['tracker']['rightScalars'] = $this->Tracker_Trend->getScalars($rightTrend, $startDate, $endDate);
      }
    $this->view->json['tracker']['initialStartDate'] = date('n/j/Y', strtotime($startDate));
    $this->view->json['tracker']['initialEndDate'] = date('n/j/Y', strtotime($endDate));
    $this->view->json['tracker']['trendIds'] = $this->_getParam('trendId');

    if(isset($yMin) && isset($yMax))
      {
      $this->view->json['tracker']['yMin'] = (float)$yMin;
      $this->view->json['tracker']['yMax'] = (float)$yMax;
      }
    if(isset($y2Min) && isset($y2Max))
      {
      $this->view->json['tracker']['y2Min'] = (float)$y2Min;
      $this->view->json['tracker']['y2Max'] = (float)$y2Max;
      }
    }

  /**
   * Ajax action for getting a new list of scalars belonging to a set of trends for a specified date range
   * @param trendId Comma separated list of trend id's to view
   * @param startDate The start date to retrieve scalars
   * @param endDate The end date to retrieve scalars
   */
  public function scalarsAction()
    {
    $this->disableView();
    $this->disableLayout();
    $trendId = $this->_getParam('trendId');
    $rightTrendId = $this->_getParam('rightTrendId');
    $startDate = $this->_getParam('startDate');
    $endDate = $this->_getParam('endDate');
    if(!isset($trendId))
      {
      throw new Zend_Exception('Must pass trendId parameter');
      }
    $trendIds = explode(' ', trim(str_replace(',', ' ', $trendId)));
    $startDate = date('Y-m-d H:i:s', strtotime($startDate));
    $endDate = date('Y-m-d H:i:s', strtotime($endDate.' 23:59:59')); //go to end of the day
    
    $scalars = array();
    foreach($trendIds as $trendId)
      {
      $trend = $this->Tracker_Trend->load($trendId);
      $comm = $trend->getProducer()->getCommunity();
      if(!$this->Community->policyCheck($comm, $this->userSession->Dao, MIDAS_POLICY_READ))
        {
        throw new Zend_Exception('Read permission required on the community', 403);
        }
      $scalars[] = $this->Tracker_Trend->getScalars($trend, $startDate, $endDate);
      }
    $retVal = array('status' => 'ok', 'scalars' => $scalars);

    if(isset($rightTrendId))
      {
      $rightTrend = $this->Tracker_Trend->load($rightTrendId);
      if($comm->getKey() != $rightTrend->getProducer()->getCommunityId())
        {
        throw new Zend_Exception('Right trend must belong to the same community', 403);
        }
      $retVal['rightScalars'] = $this->Tracker_Trend->getScalars($rightTrend, $startDate, $endDate);
      }

    echo JsonComponent::encode($retVal);
    }

  /**
   * Delete a trend, deleting all scalar records within it (requires community admin)
   */
  public function deleteAction()
    {
    // TODO (include progress reporting)
    }

  /**
   * Show the view for editing the trend information
   */
  public function editAction()
    {
    $trendId = $this->_getParam('trendId');

    if(!isset($trendId))
      {
      throw new Zend_Exception('Must pass trendId parameter');
      }
    $trend = $this->Tracker_Trend->load($trendId);
    $comm = $trend->getProducer()->getCommunity();
    if(!$this->Community->policyCheck($comm, $this->userSession->Dao, MIDAS_POLICY_ADMIN))
      {
      throw new Zend_Exception('Admin permission required on the community', 403);
      }
    $this->view->trend = $trend;

    $header = '<ul class="pathBrowser">';
    $header .= '<li class="pathFolder"><img alt="" src="'.$this->view->coreWebroot.'/public/images/icons/community.png" /><span><a href="'.$this->view->webroot.'/community/'.$comm->getKey().'#Trackers">'.$comm->getName().'</a></span></li>';
    $header .= '<li class="pathFolder"><img alt="" src="'.$this->view->coreWebroot.'/public/images/icons/cog_go.png" /><span><a href="'.$this->view->webroot.'/tracker/producer/view?producerId='.$trend->getProducer()->getKey().'">'.$trend->getProducer()->getDisplayName().'</a></span></li>';
    $header .= '<li class="pathFolder"><img alt="" src="'.$this->view->moduleWebroot.'/public/images/chart_line.png" /><span><a href="'.$this->view->webroot.'/tracker/trend/view?trendId='.$trend->getKey().'">'.$trend->getDisplayName().'</a></span></li>';
    $header .= '</ul>';
    $this->view->header = $header;
    }

  /**
   * Handle edit form submission
   * @param trendId The id of the trend to edit
   */
  public function editsubmitAction()
    {
    $this->disableLayout();
    $this->disableView();
    $trendId = $this->_getParam('trendId');

    if(!isset($trendId))
      {
      throw new Zend_Exception('Must pass trendId parameter');
      }
    $trend = $this->Tracker_Trend->load($trendId);
    if(!$this->Community->policyCheck($trend->getProducer()->getCommunity(), $this->userSession->Dao, MIDAS_POLICY_ADMIN))
      {
      throw new Zend_Exception('Admin permission required on the community', 403);
      }
    $metricName = $this->_getParam('metricName');
    $displayName = $this->_getParam('displayName');
    $unit = $this->_getParam('unit');
    $configItemId = $this->_getParam('configItemId');
    $testItemId = $this->_getParam('testItemId');
    $truthItemId = $this->_getParam('truthItemId');

    if(isset($metricName))
      {
      $trend->setMetricName($metricName);
      }
    if(isset($displayName))
      {
      $trend->setDisplayName($displayName);
      }
    if(isset($unit))
      {
      $trend->setUnit($unit);
      }
    if(isset($configItemId))
      {
      $trend->setConfigItemId($configItemId);
      }
    if(isset($testItemId))
      {
      $trend->setTestDatasetId($testItemId);
      }
    if(isset($truthItemId))
      {
      $trend->setTruthDatasetId($truthItemId);
      }
    $this->Tracker_Trend->save($trend);
    echo JsonComponent::encode(array('status' => 'ok', 'message' => 'Changes saved'));
    }

  /**
   * Show the dialog for email notification
   * @param trendId The id of the trend
   */
  public function notifyAction()
    {
    $this->disableLayout();
    $trendId = $this->_getParam('trendId');
    if(!$this->logged)
      {
      throw new Zend_Exception('Must be logged in');
      }
    if(!isset($trendId))
      {
      throw new Zend_Exception('Must pass trendId parameter');
      }
    $trend = $this->Tracker_Trend->load($trendId);
    if(!$trend)
      {
      throw new Zend_Exception('Invalid trendId', 404);
      }
    if(!$this->Community->policyCheck($trend->getProducer()->getCommunity(), $this->userSession->Dao))
      {
      throw new Zend_Exception('Permission denied', 403);
      }
    $this->view->trend = $trend;
    $notificationModel = MidasLoader::loadModel('ThresholdNotification', $this->moduleName);
    $this->view->setting = $notificationModel->getUserSetting($this->userSession->Dao, $trend);
    }

  /**
   * Handle form submission from email notification dialog
   * @param trendId The trend id
   * @param doNotify Will be either "yes" or "no"
   * @param operator One of "<", ">", "<=", ">="
   * @param value The comparison value (must be numeric)
   */
  public function notifysubmitAction()
    {
    $this->disableLayout();
    $this->disableView();
    if(!$this->logged)
      {
      echo JsonComponent::encode(array('status' => 'error', 'message' => 'You are not logged in'));
      return;
      }
    $trendId = $this->_getParam('trendId');
    if(!isset($trendId))
      {
      throw new Zend_Exception('Must pass trendId parameter');
      }
    $trend = $this->Tracker_Trend->load($trendId);
    if(!$trend)
      {
      throw new Zend_Exception('Invalid trendId', 404);
      }
    if(!$this->Community->policyCheck($trend->getProducer()->getCommunity(), $this->userSession->Dao))
      {
      throw new Zend_Exception('Must have read permission on the community', 403);
      }
    $existing = $this->Tracker_ThresholdNotification->getUserSetting($this->userSession->Dao, $trend);
    if($existing)
      {
      $this->Tracker_ThresholdNotification->delete($existing);
      }

    $doNotify = $this->_getParam('doNotify');
    if(isset($doNotify) && $doNotify == 'yes')
      {
      $operator = $this->_getParam('operator');
      $value = $this->_getParam('value');
      if(!is_numeric($value))
        {
        echo JsonComponent::encode(array('status' => 'error', 'message' => 'Threshold value must be numeric'));
        return;
        }
      $threshold = MidasLoader::newDao('ThresholdNotificationDao', $this->moduleName);
      $threshold->setTrendId($trend->getKey());
      $threshold->setValue((float)$value);
      $threshold->setComparison($operator);
      $threshold->setAction(MIDAS_TRACKER_EMAIL_USER);
      $threshold->setRecipientId($this->userSession->Dao->getKey());
      $this->Tracker_ThresholdNotification->save($threshold);
      }
    echo JsonComponent::encode(array('status' => 'ok', 'message' => 'Changes saved'));
    }
}//end class
