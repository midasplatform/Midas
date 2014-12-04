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

/** Trend controller */
class Tracker_TrendController extends Tracker_AppController
{
    public $_components = array('Breadcrumb');
    public $_models = array('Community');
    public $_moduleModels = array('Producer', 'Scalar', 'ThresholdNotification', 'Trend');

    /**
     * View a given trend
     *
     * @param trendId Comma separated list of trends to show using the left Y axis
     * @param rightTrendId (optional) The id of the trend to display on the right Y axis
     * @param startDate (optional) The start date to retrieve scalars
     * @param endDate (optional) The end date to retrieve scalars
     * @param yMin (optional) Minimum value of the left y axis
     * @param yMax (optional) Maximum value of the left y axis
     * @param y2Min (optional) Minimum value of the right y axis
     * @param y2Max (optional) Maximum value of the right y axis
     * @throws Zend_Exception
     */
    public function viewAction()
    {
        $trendId = $this->getParam('trendId');
        $startDate = $this->getParam('startDate');
        $endDate = $this->getParam('endDate');
        $rightTrendId = $this->getParam('rightTrendId');
        $yMin = $this->getParam('yMin');
        $yMax = $this->getParam('yMax');
        $y2Min = $this->getParam('y2Min');
        $y2Max = $this->getParam('y2Max');

        if (!isset($trendId)) {
            throw new Zend_Exception('Must pass trendId parameter');
        }
        // Provide sensible default date range
        if (!isset($startDate)) {
            $startDate = strtotime('-1 month');
        } else {
            $startDate = strtotime($startDate);
        }
        if (!isset($endDate)) {
            $endDate = time();
        } else {
            $endDate = strtotime($endDate.' 23:59:59');
        }
        $startDate = date('Y-m-d H:i:s', $startDate);
        $endDate = date('Y-m-d H:i:s', $endDate);

        $userId = $this->userSession->Dao ? $this->userSession->Dao->getKey() : null;

        $trendIds = explode(' ', trim(str_replace(',', ' ', $trendId)));
        $this->view->trends = array();
        $this->view->allBranches = $this->Tracker_Scalar->getDistinctBranches();
        foreach ($trendIds as $trendId) {
            $trend = $this->Tracker_Trend->load($trendId);
            $comm = $trend->getProducer()->getCommunity();
            if (!$this->Community->policyCheck($comm, $this->userSession->Dao, MIDAS_POLICY_READ)
            ) {
                throw new Zend_Exception('Read permission required on the community', 403);
            }
            $this->view->json['tracker']['scalars'][] = $this->Tracker_Trend->getScalars(
                $trend,
                $startDate,
                $endDate,
                $userId
            );
            $this->view->json['tracker']['trends'][] = $trend;
            if (!isset($this->view->json['tracker']['producerId'])) {
                $this->view->json['tracker']['producerId'] = $trend->getProducerId();
            }
            $this->view->trends[] = $trend;
        }
        if (isset($rightTrendId)) {
            $rightTrend = $this->Tracker_Trend->load($rightTrendId);
            if ($comm->getKey() != $rightTrend->getProducer()->getCommunityId()
            ) {
                throw new Zend_Exception('Right trend must belong to the same community', 403);
            }
            $this->view->rightTrend = $rightTrend;
        }
        $this->view->isAdmin = $this->Community->policyCheck($comm, $this->userSession->Dao, MIDAS_POLICY_ADMIN);
        $this->view->logged = $this->logged;

        $breadcrumbs = array();
        $breadcrumbs[] = array('type' => 'community', 'object' => $comm, 'tab' => 'Trackers');
        $breadcrumbs[] = array(
            'type' => 'custom',
            'text' => $trend->getProducer()->getDisplayName(),
            'icon' => $this->view->coreWebroot.'/public/images/icons/cog_go.png',
            'href' => $this->view->webroot.'/tracker/producer/view?producerId='.$trend->getProducer()->getKey(),
        );

        if (count($this->view->trends) == 1) {
            $text = $this->view->trends[0]->getDisplayName();
        } else {
            $text = count($this->view->trends).' trends';
        }
        if ($this->view->rightTrend) {
            $text .= ' &amp; '.$rightTrend->getDisplayName();
        }
        $breadcrumbs[] = array(
            'type' => 'custom',
            'text' => $text,
            'icon' => $this->view->moduleWebroot.'/public/images/chart_line.png',
        );
        $this->Component->Breadcrumb->setBreadcrumbHeader($breadcrumbs, $this->view);

        if (isset($rightTrend)) {
            $this->view->json['tracker']['rightTrend'] = $rightTrend;
            $this->view->json['tracker']['rightScalars'] = $this->Tracker_Trend->getScalars(
                $rightTrend,
                $startDate,
                $endDate,
                $userId
            );
        }
        $this->view->json['tracker']['initialStartDate'] = date('n/j/Y', strtotime($startDate));
        $this->view->json['tracker']['initialEndDate'] = date('n/j/Y', strtotime($endDate));
        $this->view->json['tracker']['trendIds'] = $this->getParam('trendId');

        if (isset($yMin) && isset($yMax)) {
            $this->view->json['tracker']['yMin'] = (float) $yMin;
            $this->view->json['tracker']['yMax'] = (float) $yMax;
        }
        if (isset($y2Min) && isset($y2Max)) {
            $this->view->json['tracker']['y2Min'] = (float) $y2Min;
            $this->view->json['tracker']['y2Max'] = (float) $y2Max;
        }
    }

    /**
     * Ajax action for getting a new list of scalars belonging to a set of trends for a specified date range
     *
     * @param trendId Comma separated list of trend id's to view
     * @param startDate The start date to retrieve scalars
     * @param endDate The end date to retrieve scalars
     */
    public function scalarsAction()
    {
        $this->disableView();
        $this->disableLayout();
        $trendId = $this->getParam('trendId');
        $rightTrendId = $this->getParam('rightTrendId');
        $startDate = $this->getParam('startDate');
        $endDate = $this->getParam('endDate');

        $userId = $this->userSession->Dao ? $this->userSession->Dao->getKey() : null;

        if (!isset($trendId)) {
            throw new Zend_Exception('Must pass trendId parameter');
        }
        $trendIds = explode(' ', trim(str_replace(',', ' ', $trendId)));
        $startDate = date('Y-m-d H:i:s', strtotime($startDate));
        $endDate = date('Y-m-d H:i:s', strtotime($endDate.' 23:59:59')); // go to end of the day

        $scalars = array();
        foreach ($trendIds as $trendId) {
            $trend = $this->Tracker_Trend->load($trendId);
            $comm = $trend->getProducer()->getCommunity();
            if (!$this->Community->policyCheck($comm, $this->userSession->Dao, MIDAS_POLICY_READ)
            ) {
                throw new Zend_Exception('Read permission required on the community', 403);
            }
            $scalars[] = $this->Tracker_Trend->getScalars($trend, $startDate, $endDate, $userId);
        }
        $retVal = array('status' => 'ok', 'scalars' => $scalars);

        if (isset($rightTrendId)) {
            $rightTrend = $this->Tracker_Trend->load($rightTrendId);
            if ($comm->getKey() != $rightTrend->getProducer()->getCommunityId()
            ) {
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
        $this->disableLayout();
        $this->disableView();

        $trendId = $this->getParam('trendId');

        if (!isset($trendId)) {
            throw new Zend_Exception('Must pass trendId parameter');
        }
        $trend = $this->Tracker_Trend->load($trendId);
        $comm = $trend->getProducer()->getCommunity();
        if (!$this->Community->policyCheck($comm, $this->userSession->Dao, MIDAS_POLICY_ADMIN)
        ) {
            throw new Zend_Exception('Admin permission required on the community', 403);
        }
        $this->Tracker_Trend->delete($trend, $this->progressDao);
    }

    /**
     * Show the view for editing the trend information
     */
    public function editAction()
    {
        $trendId = $this->getParam('trendId');

        if (!isset($trendId)) {
            throw new Zend_Exception('Must pass trendId parameter');
        }
        $trend = $this->Tracker_Trend->load($trendId);
        $comm = $trend->getProducer()->getCommunity();
        if (!$this->Community->policyCheck($comm, $this->userSession->Dao, MIDAS_POLICY_ADMIN)
        ) {
            throw new Zend_Exception('Admin permission required on the community', 403);
        }
        $this->view->trend = $trend;

        $header = '<ul class="pathBrowser">';
        $header .= '<li class="pathFolder"><img alt="" src="'.$this->view->coreWebroot.'/public/images/icons/community.png" /><span><a href="'.$this->view->webroot.'/community/'.$comm->getKey(
            ).'#Trackers">'.$comm->getName().'</a></span></li>';
        $header .= '<li class="pathFolder"><img alt="" src="'.$this->view->coreWebroot.'/public/images/icons/cog_go.png" /><span><a href="'.$this->view->webroot.'/tracker/producer/view?producerId='.$trend->getProducer(
            )->getKey().'">'.$trend->getProducer()->getDisplayName().'</a></span></li>';
        $header .= '<li class="pathFolder"><img alt="" src="'.$this->view->moduleWebroot.'/public/images/chart_line.png" /><span><a href="'.$this->view->webroot.'/tracker/trend/view?trendId='.$trend->getKey(
            ).'">'.$trend->getDisplayName().'</a></span></li>';
        $header .= '</ul>';
        $this->view->header = $header;
    }

    /**
     * Handle edit form submission
     *
     * @param trendId The id of the trend to edit
     */
    public function editsubmitAction()
    {
        $this->disableLayout();
        $this->disableView();
        $trendId = $this->getParam('trendId');

        if (!isset($trendId)) {
            throw new Zend_Exception('Must pass trendId parameter');
        }
        $trend = $this->Tracker_Trend->load($trendId);
        if (!$this->Community->policyCheck(
            $trend->getProducer()->getCommunity(),
            $this->userSession->Dao,
            MIDAS_POLICY_ADMIN
        )
        ) {
            throw new Zend_Exception('Admin permission required on the community', 403);
        }
        $metricName = $this->getParam('metricName');
        $displayName = $this->getParam('displayName');
        $unit = $this->getParam('unit');
        $configItemId = $this->getParam('configItemId');
        $testItemId = $this->getParam('testItemId');
        $truthItemId = $this->getParam('truthItemId');

        if (isset($metricName)) {
            $trend->setMetricName($metricName);
        }
        if (isset($displayName)) {
            $trend->setDisplayName($displayName);
        }
        if (isset($unit)) {
            $trend->setUnit($unit);
        }

        if (isset($configItemId)) {
            if ($configItemId) {
                $trend->setConfigItemId($configItemId);
            } else {
                $trend->setConfigItemId(null);
            }
        }
        if (isset($testItemId)) {
            if ($testItemId) {
                $trend->setTestDatasetId($testItemId);
            } else {
                $trend->setTestDatasetId(null);
            }
        }
        if (isset($truthItemId)) {
            if ($truthItemId) {
                $trend->setTruthDatasetId($truthItemId);
            } else {
                $trend->setTruthDatasetId(null);
            }
        }
        $this->Tracker_Trend->save($trend);
        echo JsonComponent::encode(array('status' => 'ok', 'message' => 'Changes saved'));
    }

    /**
     * Show the dialog for email notification
     *
     * @param trendId The id of the trend
     */
    public function notifyAction()
    {
        $this->disableLayout();
        $trendId = $this->getParam('trendId');
        if (!$this->logged) {
            throw new Zend_Exception('Must be logged in');
        }
        if (!isset($trendId)) {
            throw new Zend_Exception('Must pass trendId parameter');
        }
        $trend = $this->Tracker_Trend->load($trendId);
        if (!$trend) {
            throw new Zend_Exception('Invalid trendId', 404);
        }
        if (!$this->Community->policyCheck($trend->getProducer()->getCommunity(), $this->userSession->Dao)
        ) {
            throw new Zend_Exception('Permission denied', 403);
        }
        $this->view->trend = $trend;

        /** @var Tracker_ThresholdNotificationModel $notificationModel */
        $notificationModel = MidasLoader::loadModel('ThresholdNotification', $this->moduleName);
        $this->view->setting = $notificationModel->getUserSetting($this->userSession->Dao, $trend);
    }

    /**
     * Handle form submission from email notification dialog
     *
     * @param trendId The trend id
     * @param doNotify Will be either "yes" or "no"
     * @param operator One of "<", ">", "<=", ">="
     * @param value The comparison value (must be numeric)
     */
    public function notifysubmitAction()
    {
        $this->disableLayout();
        $this->disableView();
        if (!$this->logged) {
            echo JsonComponent::encode(array('status' => 'error', 'message' => 'You are not logged in'));

            return;
        }
        $trendId = $this->getParam('trendId');
        if (!isset($trendId)) {
            throw new Zend_Exception('Must pass trendId parameter');
        }
        $trend = $this->Tracker_Trend->load($trendId);
        if (!$trend) {
            throw new Zend_Exception('Invalid trendId', 404);
        }
        if (!$this->Community->policyCheck($trend->getProducer()->getCommunity(), $this->userSession->Dao)
        ) {
            throw new Zend_Exception('Must have read permission on the community', 403);
        }
        $existing = $this->Tracker_ThresholdNotification->getUserSetting($this->userSession->Dao, $trend);
        if ($existing) {
            $this->Tracker_ThresholdNotification->delete($existing);
        }

        $doNotify = $this->getParam('doNotify');
        if (isset($doNotify) && $doNotify == 'yes') {
            $operator = $this->getParam('operator');
            $value = $this->getParam('value');
            if (!is_numeric($value)) {
                echo JsonComponent::encode(array('status' => 'error', 'message' => 'Threshold value must be numeric'));

                return;
            }

            /** @var Tracker_ThresholdNotificationDao $threshold */
            $threshold = MidasLoader::newDao('ThresholdNotificationDao', $this->moduleName);
            $threshold->setTrendId($trend->getKey());
            $threshold->setValue((float) $value);
            $threshold->setComparison($operator);
            $threshold->setAction(MIDAS_TRACKER_EMAIL_USER);
            $threshold->setRecipientId($this->userSession->Dao->getKey());
            $this->Tracker_ThresholdNotification->save($threshold);
        }
        echo JsonComponent::encode(array('status' => 'ok', 'message' => 'Changes saved'));
    }
}
