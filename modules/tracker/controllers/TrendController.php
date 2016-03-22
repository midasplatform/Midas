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
 * Trend controller for the tracker module.
 *
 * @property Tracker_ScalarModel $Tracker_Scalar
 * @property Tracker_ThresholdNotificationModel $Tracker_ThresholdNotification
 * @property Tracker_TrendModel $Tracker_Trend
 * @property Tracker_SubmissionModel $Tracker_Submission
 * @property Tracker_ProducerModel $Tracker_Producer
 */
class Tracker_TrendController extends Tracker_AppController
{
    /** @var array */
    public $_components = array('Breadcrumb');

    /** @var array */
    public $_moduleModels = array('Producer', 'Scalar', 'ThresholdNotification', 'Trend', 'Submission');

    /**
     * View a given trend.
     *
     * Request parameters:
     *     trendId - Comma separated list of trends to show using the left Y axis
     *     rightTrendId (optional) - The id of the trend to display on the right Y axis
     *     startDate (optional) - The start date to retrieve scalars
     *     endDate (optional) - The end date to retrieve scalars
     *     yMin (optional) - Minimum value of the left y axis
     *     yMax (optional) - Maximum value of the left y axis
     *     y2Min (optional) - Minimum value of the right y axis
     *     y2Max (optional) - Maximum value of the right y axis
     *
     * @throws Zend_Exception
     */
    public function viewAction()
    {
        /** @var int $trendId */
        $trendId = $this->getParam('trendId');

        if (!isset($trendId)) {
            throw new Zend_Exception('The required trendId parameter is missing');
        }

        /** @var string $startDate */
        $startDate = $this->getParam('startDate');

        // Provide sensible default date range
        if (!isset($startDate)) {
            $startDate = strtotime('-1 month');
        } else {
            $startDate = strtotime($startDate);
        }

        /** @var string $endDate */
        $endDate = $this->getParam('endDate');

        if (!isset($endDate)) {
            $endDate = time();
        } else {
            $endDate = strtotime($endDate.' 23:59:59');
        }

        $startDate = date('Y-m-d H:i:s', $startDate);
        $endDate = date('Y-m-d H:i:s', $endDate);

        $userId = $this->userSession->Dao ? $this->userSession->Dao->getKey() : null;

        $this->view->allBranches = $this->Tracker_Submission->getDistinctBranches();

        $trendIds = explode(' ', trim(str_replace(',', ' ', $trendId)));
        $trendDaos = array();

        /** @var int $trendId */
        foreach ($trendIds as $trendId) {
            /** @var Tracker_TrendDao $trendDao */
            $trendDao = $this->Tracker_Trend->load($trendId);

            if ($this->Tracker_Trend->policyCheck($trendDao, $this->userSession->Dao, MIDAS_POLICY_READ) === false
            ) {
                throw new Zend_Exception('The trend does not exist or you do not have the necessary permission', 403);
            }

            $this->view->json['tracker']['scalars'][] = $this->Tracker_Trend->getScalars(
                $trendDao,
                $startDate,
                $endDate,
                $userId
            );

            if (!isset($this->view->json['tracker']['producerId'])) {
                $this->view->json['tracker']['producerId'] = $trendDao->getProducerId();
            }

            $trendDaos[] = $trendDao;
        }

        /** @var Tracker_TrendDao $trendDao */
        $trendDao = $trendDaos[0];
        $producerDao = $trendDao->getProducer();
        $communityDao = $producerDao->getCommunity();

        if (count($trendDaos) === 1) {
            $text = $trendDao->getDisplayName();
        } else {
            $text = count($trendDaos).' trends';
        }

        $this->view->trends = $trendDaos;
        $this->view->json['tracker']['trends'] = $trendDaos;

        /** @var int $rightTrendId */
        $rightTrendId = $this->getParam('rightTrendId');

        if (isset($rightTrendId)) {
            /** @var Tracker_TrendDao $rightTrendDao */
            $rightTrendDao = $this->Tracker_Trend->load($rightTrendId);

            if ($communityDao !== false && $communityDao->getKey() !== $rightTrendDao->getProducer()->getCommunityId()
            ) {
                throw new Zend_Exception('The right trend must belong to the same community as the other trends', 403);
            }

            $text .= ' &amp; '.$rightTrendDao->getDisplayName();

            $this->view->rightTrend = $rightTrendDao;
            $this->view->json['tracker']['rightTrend'] = $rightTrendDao;
            $this->view->json['tracker']['rightScalars'] = $this->Tracker_Trend->getScalars(
                $rightTrendDao,
                $startDate,
                $endDate,
                $userId
            );
        }

        $this->view->isAdmin = $this->Tracker_Trend->policyCheck($trendDao, $this->userSession->Dao, MIDAS_POLICY_ADMIN);
        $this->view->logged = $this->logged;

        $breadcrumbs = array(
            array(
                'type' => 'community',
                'object' => $communityDao,
                'tab' => 'Trackers',
            ),
            array(
                'type' => 'custom',
                'text' => $producerDao->getDisplayName(),
                'icon' => $this->view->coreWebroot.'/public/images/icons/cog_go.png',
                'href' => $this->view->webroot.'/tracker/producer/view?producerId='.$producerDao->getKey(),
            ),
            array(
                'type' => 'custom',
                'text' => $text,
                'icon' => $this->view->moduleWebroot.'/public/images/chart_line.png',
            ),
        );
        $this->Component->Breadcrumb->setBreadcrumbHeader($breadcrumbs, $this->view);

        $this->view->json['tracker']['initialStartDate'] = date('n/j/Y', strtotime($startDate));
        $this->view->json['tracker']['initialEndDate'] = date('n/j/Y', strtotime($endDate));
        $this->view->json['tracker']['trendIds'] = $this->getParam('trendId');

        /** @var float $yMin */
        $yMin = $this->getParam('yMin');

        /** @var float $yMax */
        $yMax = $this->getParam('yMax');

        if (isset($yMin) && isset($yMax)) {
            $this->view->json['tracker']['yMin'] = (float) $yMin;
            $this->view->json['tracker']['yMax'] = (float) $yMax;
        }

        /** @var float $y2Min */
        $y2Min = $this->getParam('y2Min');

        /** @var float $y2Max */
        $y2Max = $this->getParam('y2Max');

        if (isset($y2Min) && isset($y2Max)) {
            $this->view->json['tracker']['y2Min'] = (float) $y2Min;
            $this->view->json['tracker']['y2Max'] = (float) $y2Max;
        }
    }

    /**
     * AJAX action for getting a new list of scalars belonging to a set of trends for a specified date range.
     *
     * Request parameters:
     *     trendId - Comma separated list of trend id's to view
     *     startDate - The start date to retrieve scalars
     *     endDate - The end date to retrieve scalars
     *
     * @throws Zend_Exception
     */
    public function scalarsAction()
    {
        $this->disableView();
        $this->disableLayout();

        /** @var int $trendId */
        $trendId = $this->getParam('trendId');

        if (!isset($trendId)) {
            throw new Zend_Exception('The required trendId parameter is missing');
        }

        /** @var string $startDate */
        $startDate = $this->getParam('startDate');
        $startDate = date('Y-m-d H:i:s', strtotime($startDate));

        /** @var string $endDate */
        $endDate = $this->getParam('endDate');
        $endDate = date('Y-m-d H:i:s', strtotime($endDate.' 23:59:59')); // go to end of the day

        $userId = $this->userSession->Dao ? $this->userSession->Dao->getKey() : null;
        $trendIds = explode(' ', trim(str_replace(',', ' ', $trendId)));
        $scalarDaos = array();

        /** @var int $trendId */
        foreach ($trendIds as $trendId) {
            /** @var Tracker_TrendDao $trendDao */
            $trendDao = $this->Tracker_Trend->load($trendId);

            if ($this->Tracker_Trend->policyCheck($trendDao, $this->userSession->Dao, MIDAS_POLICY_READ) === false
            ) {
                throw new Zend_Exception('The trend does not exist or you do not have the necessary permission', 403);
            }

            $scalarDaos[] = $this->Tracker_Trend->getScalars($trendDao, $startDate, $endDate, $userId);
        }

        $results = array('status' => 'ok', 'scalars' => $scalarDaos);

        /** @var int $rightTrendId */
        $rightTrendId = $this->getParam('rightTrendId');

        if (isset($rightTrendId)) {
            /** @var Tracker_TrendDao $rightTrendDao */
            $rightTrendDao = $this->Tracker_Trend->load($rightTrendId);
            $trendDao = $this->Tracker_Trend->load($trendIds[0]);
            $communityDao = $trendDao->getProducer()->getCommunity();

            if ($communityDao !== false && $communityDao->getKey() !== $rightTrendDao->getProducer()->getCommunityId()
            ) {
                throw new Zend_Exception('The right trend must belong to the same community as the other trends',
                    403);
            }

            $results['rightScalars'] = $this->Tracker_Trend->getScalars($rightTrendDao, $startDate, $endDate);
        }

        echo JsonComponent::encode($results);
    }

    /**
     * Delete a trend, deleting all scalar records within it (requires community admin).
     *
     * @throws Zend_Exception
     */
    public function deleteAction()
    {
        $this->disableLayout();
        $this->disableView();

        /** @var int $trendId */
        $trendId = $this->getParam('trendId');

        if (!isset($trendId)) {
            throw new Zend_Exception('The required trendId parameter is missing');
        }

        /** @var Tracker_TrendDao $trendDao */
        $trendDao = $this->Tracker_Trend->load($trendId);

        if ($this->Tracker_Trend->policyCheck($trendDao, $this->userSession->Dao, MIDAS_POLICY_ADMIN) === false
        ) {
            throw new Zend_Exception('The trend does not exist or you do not have the necessary permission', 403);
        }

        $this->Tracker_Trend->delete($trendDao, $this->progressDao);
    }

    /**
     * Show the view for editing the trend information.
     *
     * @throws Zend_Exception
     */
    public function editAction()
    {
        /** @var int $trendId */
        $trendId = $this->getParam('trendId');

        if (!isset($trendId)) {
            throw new Zend_Exception('The required trendId parameter is missing');
        }

        /** @var Tracker_TrendDao $trendDao */
        $trendDao = $this->Tracker_Trend->load($trendId);

        if ($this->Tracker_Trend->policyCheck($trendDao, $this->userSession->Dao, MIDAS_POLICY_ADMIN) === false
        ) {
            throw new Zend_Exception('The trend does not exist or you do not have the necessary permission', 403);
        }

        $this->view->trend = $trendDao;
        $communityDao = $trendDao->getProducer()->getCommunity();

        $header = '<ul class="pathBrowser">';
        $header .= '<li class="pathFolder"><img alt="" src="'.$this->view->coreWebroot.'/public/images/icons/community.png" /><span><a href="'.$this->view->webroot.'/community/'.$communityDao->getKey(
            ).'#Trackers">'.$communityDao->getName().'</a></span></li>';
        $header .= '<li class="pathFolder"><img alt="" src="'.$this->view->coreWebroot.'/public/images/icons/cog_go.png" /><span><a href="'.$this->view->webroot.'/tracker/producer/view?producerId='.$trendDao->getProducer(
            )->getKey().'">'.$trendDao->getProducer()->getDisplayName().'</a></span></li>';
        $header .= '<li class="pathFolder"><img alt="" src="'.$this->view->moduleWebroot.'/public/images/chart_line.png" /><span><a href="'.$this->view->webroot.'/tracker/trend/view?trendId='.$trendDao->getKey(
            ).'">'.$trendDao->getDisplayName().'</a></span></li>';
        $header .= '</ul>';
        $this->view->header = $header;
    }

    /**
     * Handle edit form submission.
     *
     * Request parameters:
     *     trendId - The id of the trend to edit
     *
     * @throws Zend_Exception
     */
    public function editsubmitAction()
    {
        $this->disableLayout();
        $this->disableView();

        /** @var int $trendId */
        $trendId = $this->getParam('trendId');

        if (!isset($trendId)) {
            throw new Zend_Exception('The required trendId parameter is missing');
        }

        /** @var Tracker_TrendDao $trendDao */
        $trendDao = $this->Tracker_Trend->load($trendId);

        if ($this->Tracker_Trend->policyCheck($trendDao, $this->userSession->Dao, MIDAS_POLICY_ADMIN) === false
        ) {
            throw new Zend_Exception('The trend does not exist or you do not have the necessary permission', 403);
        }

        /** @var string $metricName */
        $metricName = $this->getParam('metricName');

        if (isset($metricName)) {
            $trendDao->setMetricName($metricName);
        }

        /** @var string $displayName */
        $displayName = $this->getParam('displayName');

        if (isset($displayName)) {
            $trendDao->setDisplayName($displayName);
        }

        /** @var string $unit */
        $unit = $this->getParam('unit');

        if (isset($unit)) {
            $trendDao->setUnit($unit);
        }

        /** @var int $configItemId */
        $configItemId = $this->getParam('configItemId');

        if (isset($configItemId)) {
            if ($configItemId) {
                $trendDao->setConfigItemId($configItemId);
            } else {
                $trendDao->setConfigItemId(null);
            }
        }

        /** @var int $testItemId */
        $testItemId = $this->getParam('testItemId');

        if (isset($testItemId)) {
            if ($testItemId) {
                $trendDao->setTestDatasetId($testItemId);
            } else {
                $trendDao->setTestDatasetId(null);
            }
        }

        /** @var int $truthItemId */
        $truthItemId = $this->getParam('truthItemId');

        if (isset($truthItemId)) {
            if ($truthItemId) {
                $trendDao->setTruthDatasetId($truthItemId);
            } else {
                $trendDao->setTruthDatasetId(null);
            }
        }

        $this->Tracker_Trend->save($trendDao);
        echo JsonComponent::encode(array('status' => 'ok', 'message' => 'Changes saved'));
    }

    /**
     * Show the dialog for email notification.
     *
     * Request parameters:
     *     trendId - The id of the trend
     *
     * @throws Zend_Exception
     */
    public function notifyAction()
    {
        $this->disableLayout();

        if (!$this->logged) {
            throw new Zend_Exception('Must be logged in');
        }

        /** @var int $trendId */
        $trendId = $this->getParam('trendId');

        if (!isset($trendId)) {
            throw new Zend_Exception('The required trendId parameter is missing');
        }

        /** @var Tracker_TrendDao $trendDao */
        $trendDao = $this->Tracker_Trend->load($trendId);

        if ($this->Tracker_Trend->policyCheck($trendDao, $this->userSession->Dao, MIDAS_POLICY_READ) === false
        ) {
            throw new Zend_Exception('The trend does not exist or you do not have the necessary permission', 403);
        }

        $this->view->trend = $trendDao;
        $this->view->setting = $this->Tracker_ThresholdNotification->getUserSetting($this->userSession->Dao, $trendDao);
    }

    /**
     * Handle form submission from email notification dialog.
     *
     * Request parameters:
     *     trendId - The trend id
     *     doNotify - Will be either "yes" or "no"
     *     operator - One of "<", ">", "<=", ">=", "==", "!="
     *     value - The comparison value (must be numeric)
     *
     * @throws Zend_Exception
     */
    public function notifysubmitAction()
    {
        $this->disableLayout();
        $this->disableView();

        if (!$this->logged) {
            echo JsonComponent::encode(array('status' => 'error', 'message' => 'You are not logged in'));

            return;
        }

        /** @var int $trendId */
        $trendId = $this->getParam('trendId');

        if (!isset($trendId)) {
            throw new Zend_Exception('The required trendId parameter is missing');
        }

        /** @var Tracker_TrendDao $trendDao */
        $trendDao = $this->Tracker_Trend->load($trendId);

        if ($this->Tracker_Trend->policyCheck($trendDao, $this->userSession->Dao, MIDAS_POLICY_READ) === false
        ) {
            throw new Zend_Exception('The trend does not exist or you do not have the necessary permission', 403);
        }

        $thresholdNotificationDao = $this->Tracker_ThresholdNotification->getUserSetting($this->userSession->Dao, $trendDao);

        if ($thresholdNotificationDao !== false) {
            $this->Tracker_ThresholdNotification->delete($thresholdNotificationDao);
        }

        /** @var string $doNotify */
        $doNotify = $this->getParam('doNotify');

        if (isset($doNotify) && $doNotify === 'yes') {
            /** @var string $operator */
            $operator = $this->getParam('operator');

            /** @var float $value */
            $value = $this->getParam('value');

            if (is_numeric($value) === false) {
                echo JsonComponent::encode(array('status' => 'error', 'message' => 'Threshold value must be numeric'));

                return;
            }

            /** @var Tracker_ThresholdNotificationDao $thresholdNotificationDao */
            $thresholdNotificationDao = MidasLoader::newDao('ThresholdNotificationDao', $this->moduleName);
            $thresholdNotificationDao->setTrendId($trendDao->getKey());
            $thresholdNotificationDao->setValue((float) $value);
            $thresholdNotificationDao->setComparison($operator);
            $thresholdNotificationDao->setAction(MIDAS_TRACKER_EMAIL_USER);
            $thresholdNotificationDao->setRecipientId($this->userSession->Dao->getKey());
            $this->Tracker_ThresholdNotification->save($thresholdNotificationDao);
        }

        echo JsonComponent::encode(array('status' => 'ok', 'message' => 'Changes saved'));
    }

    /**
     * Change key metric status of a trend.
     *
     * Request parameters:
     *     trendId - The id of the trend to set as a key metric
     *     state   - The state of is_key_metric on the trend
     *
     * @throws Zend_Exception
     */
    public function setkeymetricAction()
    {
        $this->disableLayout();
        $this->disableView();

        /** @var int $trendId */
        $trendId = $this->getParam('trendId');

        /** @var int $state */
        $state = $this->getParam('state');

        if (!isset($trendId)) {
            throw new Zend_Exception('The required trendId parameter is missing.');
        }
        if (!isset($state)) {
            throw new Zend_Exception('The required state parameter is missing.');
        }

        /** @var Tracker_TrendDao $trendDao */
        $trendDao = $this->Tracker_Trend->load($trendId);

        /** @var Tracker_ProducerDao $producerDao */
        $producerDao = $trendDao->getProducer();

        if ($this->Tracker_Producer->policyCheck($producerDao, $this->userSession->Dao, MIDAS_POLICY_ADMIN) === false
        ) {
            throw new Zend_Exception('The producer does not exist or you do not have the necessary permission on its community', 403);
        }

        $trendDao->setKeyMetric($state === '1' || $state === 'true');
        $this->Tracker_Trend->save($trendDao);
    }
}
