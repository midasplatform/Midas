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

/** Component for api methods */
class Tracker_ApiComponent extends AppComponent
{
	/** @var string */
	public $moduleName = 'tracker';

    /**
     * Helper function for verifying keys in an input array
     * @throws Exception
     */
    private function _checkKeys($keys, $values)
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $values)) {
                throw new Exception('Parameter '.$key.' must be set.', -1);
            }
        }
    }

    /**
     * Helper function to get the user from token or session authentication
     */
    private function _getUser($args)
    {
        /** @var AuthenticationComponent $authComponent */
        $authComponent = MidasLoader::loadComponent('Authentication');

        return $authComponent->getUser($args, $this->userSession->Dao);
    }

    /**
     * Associate a result item with a particular scalar value.
     *
     * @param scalarIds Comma separated list of scalar ids to associate the item with
     * @param itemId The id of the item to associate with the scalar
     * @param label The label describing the nature of the association
     * @throws Exception
     */
    public function itemAssociate($args)
    {
        /** @var CommunityModel $communityModel */
        $communityModel = MidasLoader::loadModel('Community');

        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');

        /** @var Tracker_ScalarModel $scalarModel */
        $scalarModel = MidasLoader::loadModel('Scalar', 'tracker');
        $this->_checkKeys(array('scalarIds', 'itemId', 'label'), $args);
        $user = $this->_getUser($args);

        $item = $itemModel->load($args['itemId']);
        if (!$item) {
            throw new Exception('Invalid itemId', 404);
        }
        if (!$itemModel->policyCheck($item, $user, MIDAS_POLICY_READ)) {
            throw new Exception('Read permission on the item required', 403);
        }

        $scalarIds = explode(',', $args['scalarIds']);
        foreach ($scalarIds as $scalarId) {
            $scalar = $scalarModel->load($scalarId);

            if (!$scalar) {
                throw new Exception('Invalid scalarId: '.$scalarId, 404);
            }
            if (!$communityModel->policyCheck(
                $scalar->getTrend()->getProducer()->getCommunity(),
                $user,
                MIDAS_POLICY_ADMIN
            )
            ) {
                throw new Exception('Admin permission on the community required', 403);
            }
            $scalarModel->associateItem($scalar, $item, $args['label']);
        }
    }

    /**
     * Create a new scalar data point (must have write access to the community)
     *
     * @param communityId The id of the community that owns the producer
     * @param producerDisplayName The display name of the producer
     * @param metricName The metric name that identifies which trend this point belongs to
     * @param producerRevision The repository revision of the producer that produced this value
     * @param submitTime The submit timestamp. Must be parseable with PHP strtotime().
     * @param value The value of the scalar
     * @param buildResultsUrl (Optional) The URL where build results can be viewed.
     * @param extraUrls (Optional) JSON list of additional links
     * @param params (Optional) JSON object of arbitrary key/value pairs to display.
     * @param branch (Optional) The branch name within the source repository
     * @param configItemId (Optional) If this value pertains to a specific configuration item, pass its id here
     * @param testDatasetId (Optional) If this value pertains to a specific test dataset, pass its id here
     * @param truthDatasetId (Optional) If this value pertains to a specific ground truth dataset, pass its id here
     * @param silent (Optional) If set, do not perform threshold-based email notifications for this scalar
     * @param unofficial (Optional) If passed, creates an unofficial scalar visible only to the user performing the submission
     * @return The scalar dao that was created
     * @throws Exception
     */
    public function scalarAdd($args)
    {
        /** @var CommunityModel $communityModel */
        $communityModel = MidasLoader::loadModel('Community');

        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');
        $this->_checkKeys(
            array('communityId', 'producerDisplayName', 'metricName', 'value', 'producerRevision', 'submitTime'),
            $args
        );
        $user = $this->_getUser($args);

        $official = !array_key_exists('unofficial', $args);

        $community = $communityModel->load($args['communityId']);
        if (!$community || !$communityModel->policyCheck(
                $community,
                $user,
                $official ? MIDAS_POLICY_WRITE : MIDAS_POLICY_READ
            )
        ) {
            throw new Exception('Write permission required on community', 403);
        }

        $producerDisplayName = trim($args['producerDisplayName']);
        if ($producerDisplayName == '') {
            throw new Exception('Producer display name must not be empty', -1);
        }

        /** @var Tracker_ProducerModel $producerModel */
        $producerModel = MidasLoader::loadModel('Producer', 'tracker');
        $producer = $producerModel->createIfNeeded($community->getKey(), $producerDisplayName);

        $metricName = trim($args['metricName']);
        if ($metricName == '') {
            throw new Exception('Metric name must not be empty', -1);
        }

        list($configItemId, $testDatasetId, $truthDatasetId) = array(null, null, null);
        if (isset($args['configItemId'])) {
            $configItemId = $args['configItemId'];
            $configItem = $itemModel->load($configItemId);
            if (!$configItem || !$itemModel->policyCheck($configItem, $user, MIDAS_POLICY_READ)
            ) {
                throw new Exception('Read permission required on config item', 403);
            }
        } elseif (isset($args['configItemName'])) {
            $configItem = $this->_createOrFindByName($args['configItemName'], $community);
            $configItemId = $configItem->getKey();
            if (!$configItem || !$itemModel->policyCheck($configItem, $user, MIDAS_POLICY_READ)
            ) {
                throw new Exception('Read permission required on config item', 403);
            }
        }

        if (isset($args['testDatasetId'])) {
            $testDatasetId = $args['testDatasetId'];
            $testDatasetItem = $itemModel->load($testDatasetId);
            if (!$testDatasetItem || !$itemModel->policyCheck($testDatasetItem, $user, MIDAS_POLICY_READ)
            ) {
                throw new Exception('Read permission required on test dataset item', 403);
            }
        } elseif (isset($args['testDatasetName'])) {
            $testDatasetItem = $this->_createOrFindByName($args['testDatasetName'], $community);
            $testDatasetId = $testDatasetItem->getKey();
            if (!$testDatasetItem || !$itemModel->policyCheck($testDatasetItem, $user, MIDAS_POLICY_READ)
            ) {
                throw new Exception('Read permission required on test dataset item', 403);
            }
        }

        if (isset($args['truthDatasetId'])) {
            $truthDatasetId = $args['truthDatasetId'];
            $truthDatasetItem = $itemModel->load($truthDatasetId);
            if (!$truthDatasetItem || !$itemModel->policyCheck($truthDatasetItem, $user, MIDAS_POLICY_READ)
            ) {
                throw new Exception('Read permission required on truth dataset item', 403);
            }
        } elseif (isset($args['truthDatasetName'])) {
            $truthDatasetItem = $this->_createOrFindByName($args['truthDatasetName'], $community);
            $truthDatasetId = $truthDatasetItem->getKey();
            if (!$truthDatasetItem || !$itemModel->policyCheck($truthDatasetItem, $user, MIDAS_POLICY_READ)
            ) {
                throw new Exception('Read permission required on truth dataset item', 403);
            }
        }

        if (isset($args['params'])) {
            $extraParams = json_decode($args['params'], true);
        } else {
            $extraParams = null;
        }

        if (isset($args['extraUrls'])) {
            $extraUrls = json_decode($args['extraUrls'], true);
        } else {
            $extraUrls = null;
        }

        $buildResultsUrl = isset($args['buildResultsUrl']) ? $args['buildResultsUrl'] : '';
        $branch = isset($args['branch']) ? $args['branch'] : '';

        /** @var Tracker_TrendModel $trendModel */
        $trendModel = MidasLoader::loadModel('Trend', 'tracker');
        $trend = $trendModel->createIfNeeded(
            $producer->getKey(),
            $metricName,
            $configItemId,
            $testDatasetId,
            $truthDatasetId
        );

        $submitTime = strtotime($args['submitTime']);
        if ($submitTime === false) {
            throw new Exception('Invalid submitTime value: '.$args['submitTime'], -1);
        }
        $submitTime = date('Y-m-d H:i:s', $submitTime);

        $value = (float) $args['value'];

        $producerRevision = trim($args['producerRevision']);

        /** @var Tracker_ScalarModel $scalarModel */
        $scalarModel = MidasLoader::loadModel('Scalar', 'tracker');
        $scalar = $scalarModel->addToTrend(
            $trend,
            $submitTime,
            $producerRevision,
            $value,
            $user,
            true,
            $official,
            $buildResultsUrl,
            $branch,
            $extraParams,
            $extraUrls
        );

        if (!isset($args['silent'])) {
            /** @var Tracker_ThresholdNotificationModel $notificationModel */
            $notificationModel = MidasLoader::loadModel('ThresholdNotification', 'tracker');
            $notifications = $notificationModel->getNotifications($scalar);
            $notifyComponent = MidasLoader::loadComponent('ThresholdNotification', 'tracker');
            $notifyComponent->scheduleNotifications($scalar, $notifications);
        }
        if (!$official) {
            /** @var Tracker_JobModel $jobModel */
            $jobModel = MidasLoader::loadModel('Job', 'scheduler');

            /** @var SettingModel $settingModel */
            $settingModel = MidasLoader::loadModel('Setting');
            $nHours = $settingModel->getValueByName(MIDAS_TRACKER_TEMP_SCALAR_TTL_KEY, $this->moduleName);
            if (!$nHours) {
                $nHours = 24; // default to 24 hours
            }
            while (each($notifications)) {
                /** @var Scheduler_JobDao $job */
                $job = MidasLoader::newDao('JobDao', 'scheduler');
                $job->setTask('TASK_TRACKER_DELETE_TEMP_SCALAR');
                $job->setPriority(1);
                $job->setRunOnlyOnce(1);
                $job->setFireTime(date('Y-m-d H:i:s', strtotime('+'.$nHours.' hours')));
                $job->setTimeInterval(0);
                $job->setStatus(SCHEDULER_JOB_STATUS_TORUN);
                $job->setCreatorId($user->getKey());
                $job->setParams(JsonComponent::encode(array('scalarId' => $scalar->getKey())));
                $jobModel->save($job);
            }
        }

        return $scalar;
    }

    /**
     * Upload a json file containing numeric scoring results to be added as scalars. File is parsed and then deleted from the server.
     *
     * @param communityId The id of the community that owns the producer
     * @param producerDisplayName The display name of the producer
     * @param producerRevision The repository revision of the producer that produced this value
     * @param submitTime (Optional) The submit timestamp. Must be parseable with PHP strtotime(). If not set, uses current time.
     * @param buildResultsUrl (Optional) The URL where build results can be viewed.
     * @param branch (Optional) The branch name within the source repository
     * @param extraUrls (Optional) JSON list of additional links
     * @param params (Optional) JSON object of arbitrary key/value pairs to display.
     * @param configItemId (Optional) If this value pertains to a specific configuration item, pass its id here
     * @param testDatasetId (Optional) If this value pertains to a specific test dataset, pass its id here
     * @param truthDatasetId (Optional) If this value pertains to a specific ground truth dataset, pass its id here
     * @param parentKeys (Optional) Semicolon -separated list of parent keys to look for numeric results under.  Use '.' to denote nesting, like in normal javascript syntax.
     * @param silent (Optional) If set, do not perform threshold-based email notifications for this scalar
     * @param unofficial (Optional) If passed, creates an unofficial scalar visible only to the user performing the submission
     * @return The list of scalars that were created.  Non-numeric values are ignored.
     * @throws Exception
     */
    public function resultsUploadJson($args)
    {
        /** @var CommunityModel $communityModel */
        $communityModel = MidasLoader::loadModel('Community');

        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');
        $this->_checkKeys(array('communityId', 'producerDisplayName', 'producerRevision'), $args);
        $user = $this->_getUser($args);

        $official = !array_key_exists('unofficial', $args);
        if (!$official) {
            /** @var Scheduler_JobModel $jobModel */
            $jobModel = MidasLoader::loadModel('Job', 'scheduler');

            /** @var SettingModel $settingModel */
            $settingModel = MidasLoader::loadModel('Setting');
            $nHours = $settingModel->getValueByName(MIDAS_TRACKER_TEMP_SCALAR_TTL_KEY, $this->moduleName);
            if (!$nHours) {
                $nHours = MIDAS_TRACKER_TEMP_SCALAR_TTL_DEFAULT_VALUE; // default to 24 hours
            }
        }

        // Unofficial submissions only require read access to the community
        $community = $communityModel->load($args['communityId']);
        if (!$community || !$communityModel->policyCheck(
                $community,
                $user,
                $official ? MIDAS_POLICY_WRITE : MIDAS_POLICY_READ
            )
        ) {
            throw new Exception('Write permission required on community', 403);
        }

        $producerDisplayName = trim($args['producerDisplayName']);
        if ($producerDisplayName == '') {
            throw new Exception('Producer display name must not be empty', -1);
        }

        /** @var Tracker_ProducerModel $producerModel */
        $producerModel = MidasLoader::loadModel('Producer', 'tracker');
        $producer = $producerModel->createIfNeeded($community->getKey(), $producerDisplayName);
        $buildResultsUrl = isset($args['buildResultsUrl']) ? $args['buildResultsUrl'] : '';
        $branch = isset($args['branch']) ? $args['branch'] : '';

        list($configItemId, $testDatasetId, $truthDatasetId) = array(null, null, null);
        if (isset($args['configItemId'])) {
            $configItemId = $args['configItemId'];
            $configItem = $itemModel->load($configItemId);
            if (!$configItem || !$itemModel->policyCheck($configItem, $user, MIDAS_POLICY_READ)
            ) {
                throw new Exception('Read permission required on config item', 403);
            }
        } elseif (isset($args['configItemName'])) {
            $configItem = $this->_createOrFindByName($args['configItemName'], $community);
            $configItemId = $configItem->getKey();
            if (!$configItem || !$itemModel->policyCheck($configItem, $user, MIDAS_POLICY_READ)
            ) {
                throw new Exception('Read permission required on config item', 403);
            }
        }

        if (isset($args['testDatasetId'])) {
            $testDatasetId = $args['testDatasetId'];
            $testDatasetItem = $itemModel->load($testDatasetId);
            if (!$testDatasetItem || !$itemModel->policyCheck($testDatasetItem, $user, MIDAS_POLICY_READ)
            ) {
                throw new Exception('Read permission required on test dataset item', 403);
            }
        } elseif (isset($args['testDatasetName'])) {
            $testDatasetItem = $this->_createOrFindByName($args['testDatasetName'], $community);
            $testDatasetId = $testDatasetItem->getKey();
            if (!$testDatasetItem || !$itemModel->policyCheck($testDatasetItem, $user, MIDAS_POLICY_READ)
            ) {
                throw new Exception('Read permission required on test dataset item', 403);
            }
        }

        if (isset($args['truthDatasetId'])) {
            $truthDatasetId = $args['truthDatasetId'];
            $truthDatasetItem = $itemModel->load($truthDatasetId);
            if (!$truthDatasetItem || !$itemModel->policyCheck($truthDatasetItem, $user, MIDAS_POLICY_READ)
            ) {
                throw new Exception('Read permission required on truth dataset item', 403);
            }
        } elseif (isset($args['truthDatasetName'])) {
            $truthDatasetItem = $this->_createOrFindByName($args['truthDatasetName'], $community);
            $truthDatasetId = $truthDatasetItem->getKey();
            if (!$truthDatasetItem || !$itemModel->policyCheck($truthDatasetItem, $user, MIDAS_POLICY_READ)
            ) {
                throw new Exception('Read permission required on truth dataset item', 403);
            }
        }

        if (isset($args['params'])) {
            $extraParams = json_decode($args['params'], true);
        } else {
            $extraParams = null;
        }

        if (isset($args['extraUrls'])) {
            $extraUrls = json_decode($args['extraUrls'], true);
        } else {
            $extraUrls = null;
        }

        /** @var Tracker_TrendModel $trendModel */
        $trendModel = MidasLoader::loadModel('Trend', 'tracker');

        if (isset($args['submitTime'])) {
            $submitTime = strtotime($args['submitTime']);
            if ($submitTime === false) {
                throw new Exception('Invalid submitTime value: '.$args['submitTime'], -1);
            }
            $submitTime = date('Y-m-d H:i:s', $submitTime);
        } else {
            $submitTime = date('Y-m-d H:i:s'); // Use current time if no submit time is explicitly set
        }

        $producerRevision = trim($args['producerRevision']);

        /** @var Tracker_ScalarModel $scalarModel */
        $scalarModel = MidasLoader::loadModel('Scalar', 'tracker');
        $json = json_decode(file_get_contents('php://input'), true);
        if ($json === null) {
            throw new Exception('Invalid JSON upload contents', -1);
        }
        $scalars = array();

        if (isset($args['parentKeys'])) { // iterate through all child keys of the set of specified parent keys
            $parentKeys = explode(';', $args['parentKeys']);
            foreach ($parentKeys as $parentKey) {
                $nodes = explode('.', $parentKey);
                $currentArr = $json;
                foreach ($nodes as $node) {
                    if (!isset($currentArr[$node]) || !is_array($currentArr[$node])
                    ) {
                        throw new Exception(
                            'Specified parent key "'.$parentKey.'" does not exist or is not an array type', -1
                        );
                    }
                    $currentArr = $currentArr[$node];
                }
                foreach ($currentArr as $metricName => $value) { // iterate through all children of this parent key
                    if (!is_numeric($value)) { // ignore non-numeric child keys
                        continue;
                    }
                    $trend = $trendModel->createIfNeeded(
                        $producer->getKey(),
                        $metricName,
                        $configItemId,
                        $testDatasetId,
                        $truthDatasetId
                    );
                    $scalar = $scalarModel->addToTrend(
                        $trend,
                        $submitTime,
                        $producerRevision,
                        $value,
                        $user,
                        true,
                        $official,
                        $buildResultsUrl,
                        $branch,
                        $extraParams,
                        $extraUrls
                    );
                    $scalars[] = $scalar;

                    if (!isset($args['silent'])) {
                        /** @var Tracker_ThresholdNotificationModel $notificationModel */
                        $notificationModel = MidasLoader::loadModel('ThresholdNotification', 'tracker');
                        $notifications = $notificationModel->getNotifications($scalar);
                        $notifyComponent = MidasLoader::loadComponent('ThresholdNotification', 'tracker');
                        $notifyComponent->scheduleNotifications($scalar, $notifications);
                    }
                    if (!$official) {
                        while (each($notifications)) {
                            /** @var Scheduler_JobDao $job */
                            $job = MidasLoader::newDao('JobDao', 'scheduler');
                            $job->setTask('TASK_TRACKER_DELETE_TEMP_SCALAR');
                            $job->setPriority(1);
                            $job->setRunOnlyOnce(1);
                            $job->setFireTime(date('Y-m-d H:i:s', strtotime('+'.$nHours.' hours')));
                            $job->setTimeInterval(0);
                            $job->setStatus(SCHEDULER_JOB_STATUS_TORUN);
                            $job->setCreatorId($user->getKey());
                            $job->setParams(JsonComponent::encode(array('scalarId' => $scalar->getKey())));
                            $jobModel->save($job);
                        }
                    }
                }
            }
        } else { // just read all the top level keys
            foreach ($json as $metricName => $value) {
                if (!is_numeric($value)) {
                    continue;
                }
                $trend = $trendModel->createIfNeeded(
                    $producer->getKey(),
                    $metricName,
                    $configItemId,
                    $testDatasetId,
                    $truthDatasetId
                );
                $scalar = $scalarModel->addToTrend(
                    $trend,
                    $submitTime,
                    $producerRevision,
                    $value,
                    $user,
                    true,
                    $official
                );
                $scalars[] = $scalar;

                if (!isset($args['silent'])) {
                    /** @var Tracker_ThresholdNotificationModel $notificationModel */
                    $notificationModel = MidasLoader::loadModel('ThresholdNotification', 'tracker');
                    $notifications = $notificationModel->getNotifications($scalar);
                    $notifyComponent = MidasLoader::loadComponent('ThresholdNotification', 'tracker');
                    $notifyComponent->scheduleNotifications($scalar, $notifications);
                }
            }
        }

        return $scalars;
    }

    /**
     * @param string $itemName
     * @param CommunityDao $community
     * @return ItemDao
     */
    private function _createOrFindByName($itemName, $community)
    {
        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');
        $items = $itemModel->getByName($itemName);
        if (count($items) == 0) {
            return $itemModel->createItem($itemName, '', $community->getPrivateFolder());
        } else {
            return $items[0];
        }
    }
}
