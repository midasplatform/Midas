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

/** API component for the tracker module. */
class Tracker_ApiComponent extends AppComponent
{
    /** @var string */
    public $moduleName = 'tracker';

    /**
     * Helper function for verifying keys in an input array.
     *
     * @param array $keys keys
     * @param array $values values
     * @throws Exception
     */
    private function _checkKeys($keys, $values)
    {
        /** @var string $key */
        foreach ($keys as $key) {
            if (!array_key_exists($key, $values)) {
                throw new Exception('Parameter '.$key.' must be set.', -1);
            }
        }
    }

    /**
     * Helper function to get the user from token or session authentication.
     *
     * @param array $args parameters
     * @return false|UserDao user DAO or false on failure
     */
    private function _getUser($args)
    {
        /** @var AuthenticationComponent $authComponent */
        $authComponent = MidasLoader::loadComponent('Authentication');

        return $authComponent->getUser($args, $this->userSession->Dao);
    }

    /**
     * Associate a result item with a particular submission.
     *
     * @param submissionUuid the uuid of the submission to associate the item with
     * @param itemId The id of the item to associate with the submission
     * @param label The label describing the nature of the association
     * @param testDatasetId (Optional) the id for the test dataset
     * @param truthDatasetId (Optional) the id of the truth dataset
     * @param configItemId (Optional) the id of the config dataset
     * @throws Exception
     */
    public function itemAssociate($args)
    {
        /** @var CommunityModel $communityModel */
        $communityModel = MidasLoader::loadModel('Community');

        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');

        /** @var Tracker_SubmissionModel $submissionModel */
        $submissionModel = MidasLoader::loadModel('Submission', 'tracker');

        /** @var Tracker_TrendgroupModel $trendgroupModel */
        $trendgroupModel = MidasLoader::loadModel('Trendgroup', 'tracker');

        $this->_checkKeys(array('submissionUuid', 'itemId', 'label'), $args);
        $user = $this->_getUser($args);

        /** @var ItemDao $item */
        $item = $itemModel->load($args['itemId']);
        if (!$item) {
            throw new Exception('Invalid itemId', 404);
        }
        if (!$itemModel->policyCheck($item, $user, MIDAS_POLICY_READ)) {
            throw new Exception('Read permission on the item required', 403);
        }

        $submissionUuid = $args['submissionUuid'];

        /** @var Tracker_SubmissionDao $submission */
        $submission = $submissionModel->getSubmission($submissionUuid);

        if (!$submission) {
            throw new Exception('Invalid submission uuid: '.$submissionUuid, 404);
        }

        if (!$communityModel->policyCheck(
            $submission->getProducer()->getCommunity(),
            $user,
            MIDAS_POLICY_WRITE
        )) {
            throw new Exception('Write permission on the community required', 403);
        }

        $configItemId = $args['configItemId'];
        $testDatasetId = $args['testDatasetId'];
        $truthDatasetId = $args['truthDatasetId'];

        /** @var Tracker_TrendgroupDao $trendgroup */
        $trendgroup = $trendgroupModel->createIfNeeded(
            $submission->getProducer()->getKey(),
            $configItemId,
            $testDatasetId,
            $truthDatasetId
        );

        $submissionModel->associateItem($submission, $item, $args['label'], $trendgroup);
    }

    /**
     * Create a new scalar data point (must have write access to the community),
     * creating a producer along the way if the requested producer does not
     * already exist.
     *
     * @param communityId The id of the community that owns the producer
     * @param producerDisplayName The display name of the producer
     * @param metricName The metric name that identifies which trend this point belongs to
     * @param value The value of the scalar
     * @param submissionUuid the uuid of the submission. If a submission does not exist with the specified uuid, one
     *                       will be created.
     * @param configItemId (Optional) If this value pertains to a specific configuration item, pass its id here
     * @param testDatasetId (Optional) If this value pertains to a specific test dataset, pass its id here
     * @param truthDatasetId (Optional) If this value pertains to a specific ground truth dataset, pass its id here
     * @param unit (Optional) If passed, the unit of the scalar value that identifies which trend this point belongs to.
     * @return The scalar DAO that was created
     * @throws Exception
     */
    public function scalarAdd($args)
    {
        /** @var CommunityModel $communityModel */
        $communityModel = MidasLoader::loadModel('Community');

        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');
        $this->_checkKeys(
            array(
                'communityId',
                'metricName',
                'producerDisplayName',
                'submissionUuid',
                'value',
            ),
            $args
        );
        $user = $this->_getUser($args);

        /** @var CommunityDao $community */
        $community = $communityModel->load($args['communityId']);
        if (!$community || !$communityModel->policyCheck(
                $community,
                $user,
                MIDAS_POLICY_WRITE
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
            /** @var int $configItemId */
            $configItemId = $args['configItemId'];

            /** @var ItemDao $configItem */
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
            /** @var int $testDatasetId */
            $testDatasetId = $args['testDatasetId'];

            /** @var ItemDao $testDatasetItem */
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
            /** @var int $truthDatasetId */
            $truthDatasetId = $args['truthDatasetId'];

            /** @var ItemDao $truthDatasetItem */
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

        if (isset($args['unit'])) {
            $unit = $args['unit'];
        } else {
            $unit = false;
        }
        /** @var Tracker_TrendModel $trendModel */
        $trendModel = MidasLoader::loadModel('Trend', 'tracker');
        $trend = $trendModel->createIfNeeded(
            $producer->getKey(),
            $metricName,
            $configItemId,
            $testDatasetId,
            $truthDatasetId,
            $unit
        );

        $value = (float) $args['value'];

        $uuid = $args['submissionUuid'];
        $submissionModel = MidasLoader::loadModel('Submission', 'tracker');
        $submissionDao = $submissionModel->getOrCreateSubmission($producer, $uuid);

        /** @var Tracker_ScalarModel $scalarModel */
        $scalarModel = MidasLoader::loadModel('Scalar', 'tracker');
        $scalar = $scalarModel->addToTrend($trend, $submissionDao, $value);

        return $scalar;
    }

    /**
     * Create or find an item with the given name in the given community.
     *
     * @param string $itemName item name
     * @param CommunityDao $community community DAO
     * @return ItemDao item DAO
     * @throws Exception
     */
    private function _createOrFindByName($itemName, $community)
    {
        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');
        $items = $itemModel->getByName($itemName);
        if (count($items) === 0) {
            $folders = $community->getFolder()->getFolders();
            $privateFolder = null;
            /** @var FolderDao $folder */
            foreach ($folders as $folder) {
                if ($folder->getName() === 'Private' && $folder->getPrivacyStatus() === MIDAS_PRIVACY_PRIVATE) {
                    $privateFolder = $folder;
                    break;
                }
            }
            if (is_null($privateFolder)) {
                throw new Exception('No private folder in the given community in which to create an item', -1);
            }

            return $itemModel->createItem($itemName, '', $privateFolder);
        }

        return $items[0];
    }

    /**
     * Create a new submission (must have write access to the community),
     * creating a producer along the way if the requested producer does not
     * already exist.
     *
     * @param communityId The community attached to the producer
     * @param producerDisplayName Displayed name of the producer
     * @param producerRevision The repository revision of the producer that produced this value
     * @param branch The branch name within the source repository
     * @param uuid (Optional) A unique identifier for the submission. If none is passed, one will be generated.
     * @param name (Optional) A name for the submission
     * @param submitTime (Optional) The submit timestamp. Must be parseable with PHP strtotime()
     * @param buildResultsUrl (Optional) The URL where build results can be viewed
     * @param params (Optional) JSON object of arbitrary key/value pairs to display
     * @param extraUrls (Optional) JSON list of additional links
     * @param reproductionCommand (Optional) If passed, the command to produce this scalar
     *
     * @return The submission DAO that was created
     * @throws Exception
     */
    public function submissionAdd($args)
    {
        /** @var Tracker_ApisubmissionComponent $newApi */
        $newApi = MidasLoader::loadComponent('Apisubmission',
                                             'tracker');

        return $newApi->post($args);
    }

    /**
     * Update and return an array of all aggregate metrics calculated on each
     * aggregate metric spec attached to the submission identified by the passed in
     * submission uuid.
     *
     * @param uuid The uuid of the submission to calculate aggregate metrics for
     * @param notify (Optional) If set, will schedule notifications for the calculated metrics
     * if any are above their defined threshold
     * @return An array of AggregateMetricDao calculated on the submission
     * @throws Exception
     */
    public function aggregatemetricsUpdate($args)
    {
        $this->_checkKeys(array('uuid'), $args);
        $user = $this->_getUser($args);

        $uuid = $args['uuid'];
        /** @var Tracker_SubmissionModel $submissionModel */
        $submissionModel = MidasLoader::loadModel('Submission', 'tracker');
        /** @var Tracker_SubmissionDao $submissionDao */
        $submissionDao = $submissionModel->getSubmission($uuid);
        if ($submissionDao === false) {
            throw new Zend_Exception('The submission does not exist', 403);
        }
        /** @var Tracker_ProducerDao $producerDao */
        $producerDao = $submissionDao->getProducer();
        /** @var CommunityDao $communityDao */
        $communityDao = $producerDao->getCommunity();
        /** @var CommunityModel $communityModel */
        $communityModel = MidasLoader::loadModel('Community');
        if ($communityDao === false || $communityModel->policyCheck($communityDao, $user, MIDAS_POLICY_ADMIN) === false
        ) {
            throw new Zend_Exception('The associated community does not exist or you do not Admin access to the community', 403);
        }

        /** @var Tracker_AggregateMetricModel $aggregateMetricModel */
        $aggregateMetricModel = MidasLoader::loadModel('AggregateMetric', 'tracker');
        $aggregateMetrics = $aggregateMetricModel->updateAggregateMetricsForSubmission($submissionDao);

        if (array_key_exists('notify', $args) && $args['notify']) {
            /** @var Tracker_AggregateMetricNotificationModel $aggregateMetricNotificationModel */
            $aggregateMetricNotificationModel = MidasLoader::loadModel('AggregateMetricNotification', 'tracker');
            /** @var Tracker_AggregateMetricDao $aggregateMetricDao */
            foreach ($aggregateMetrics as $aggregateMetricDao) {
                /* @var array $notificationJobs */
                $notificationJobs = $aggregateMetricNotificationModel->scheduleNotificationJobs($aggregateMetricDao);
            }
        }

        return $aggregateMetrics;
    }

    /**
     * Load the aggregate metric spec with the passed in id and ensure the user
     * has read access to it.
     *
     * @param UserDao $userDao DAO
     * @param string $aggregateMetricSpecId
     * @param {MIDAS_POLICY_READ|MIDAS_POLICY_WRITE|MIDAS_POLICY_ADMIN} $policy the policy level required on the spec
     * @return AggregateMetricSpecDao aggregate metric spec DAO
     * @throws Exception
     */
    private function _loadAggregateMetricSpec($userDao, $aggregateMetricSpecId, $policy = MIDAS_POLICY_READ)
    {
        /** @var Tracker_AggregateMetricSpecModel $aggregateMetricSpecModel */
        $aggregateMetricSpecModel = MidasLoader::loadModel('AggregateMetricSpec', 'tracker');
        /** @var Tracker_AggregateMetricSpecDao $aggregateMetricSpecDao */
        $aggregateMetricSpecDao = $aggregateMetricSpecModel->load($aggregateMetricSpecId);
        if ($aggregateMetricSpecModel->policyCheck($aggregateMetricSpecDao, $userDao, $policy) === false) {
            throw new Zend_Exception('The aggregate metric spec does not exist or you do not have access to it', 403);
        }

        return $aggregateMetricSpecDao;
    }

    /**
     * Create a notification for a user against an aggregate metric notification,
     * so that whenever an aggregate metric created from that aggregate metric spec
     * is beyond the notification threshold, the user will be notified by email.
     *
     * @param userId The id of the user to tie to the notification
     * @param aggregateMetricNotificationId The id of the aggregate metric notification
     * @return UserDao the user DAO of the user who will be alerted
     * @throws Exception
     */
    public function aggregatemetricspecnotifieduserCreate($args)
    {
        $this->_checkKeys(array('userId', 'aggregateMetricNotificationId'), $args);
        $user = $this->_getUser($args);

        $aggregateMetricNotificationId = $args['aggregateMetricNotificationId'];
        /** @var Tracker_AggregateMetricNotificationModel $aggregateMetricNotificationModel */
        $aggregateMetricNotificationModel = MidasLoader::loadModel('AggregateMetricNotification', 'tracker');
        /** @var Tracker_AggregateMetricNotificationDao $aggregateMetricNotificationDao */
        $aggregateMetricNotificationDao = $aggregateMetricNotificationModel->load($aggregateMetricNotificationId);

        /** @var Tracker_AggregateMetricSpecDao $aggregateMetricSpecDao */
        $aggregateMetricSpecDao = $this->_loadAggregateMetricSpec($user, $aggregateMetricNotificationDao->getAggregateMetricSpecId(), MIDAS_POLICY_ADMIN);

        /** @var UserModel $userModel */
        $userModel = MidasLoader::loadModel('User');
        /** @var UserDao $notificationUserDao */
        $notificationUserDao = $userModel->load($args['userId']);

        $aggregateMetricNotificationModel->createUserNotification($aggregateMetricNotificationDao, $notificationUserDao);

        return $notificationUserDao;
    }

    /**
     * Delete a user from an aggregate metric notification,
     * the user will no longer receive notifications when aggregate metrics created
     * from the associated aggregate metric spec are beyond the notification threshold of the
     * notification.
     *
     * @param userId The id of the user to delete from the notification
     * @param aggregateMetricNotificationId The id of the aggregate metric notification
     * @return UserDao the user DAO of the user who will no longer be alerted
     * @throws Exception
     */
    public function aggregatemetricspecnotifieduserDelete($args)
    {
        $this->_checkKeys(array('userId', 'aggregateMetricNotificationId'), $args);
        $user = $this->_getUser($args);

        $aggregateMetricNotificationId = $args['aggregateMetricNotificationId'];
        /** @var Tracker_AggregateMetricNotificationModel $aggregateMetricNotificationModel */
        $aggregateMetricNotificationModel = MidasLoader::loadModel('AggregateMetricNotification', 'tracker');
        /** @var Tracker_AggregateMetricNotificationDao $aggregateMetricNotificationDao */
        $aggregateMetricNotificationDao = $aggregateMetricNotificationModel->load($aggregateMetricNotificationId);

        /** @var Tracker_AggregateMetricSpecDao $aggregateMetricSpecDao */
        $aggregateMetricSpecDao = $this->_loadAggregateMetricSpec($user, $aggregateMetricNotificationDao->getAggregateMetricSpecId(), MIDAS_POLICY_ADMIN);

        /** @var UserModel $userModel */
        $userModel = MidasLoader::loadModel('User');
        /** @var UserDao $notificationUserDao */
        $notificationUserDao = $userModel->load($args['userId']);

        $aggregateMetricNotificationModel->deleteUserNotification($aggregateMetricNotificationDao, $notificationUserDao);

        return $notificationUserDao;
    }

    /**
     * Return an array of associative arrays, with keys 'notification' => an AggregateMetricNotificationDao
     * and 'users' => an array of UserDaos tied to the AggregateMetricNotificationDao, for each
     * AggregateMetricNotification tied to the passed in AggregateMetricSpecId.
     *
     * @param aggregateMetricSpecId the id of the aggregate metric spec
     * @return array of associative arrays with keys 'notification' and 'users'
     */
    public function aggregatemetricspecnotificationsList($args)
    {
        $this->_checkKeys(array('aggregateMetricSpecId'), $args);
        $user = $this->_getUser($args);

        /** @var Tracker_AggregateMetricSpecDao $aggregateMetricSpecDao */
        $aggregateMetricSpecDao = $this->_loadAggregateMetricSpec($user, $args['aggregateMetricSpecId']);

        /** @var Tracker_AggregateMetricNotificationModel $aggregateMetricNotificationModel */
        $aggregateMetricNotificationModel = MidasLoader::loadModel('AggregateMetricNotification', 'tracker');
        /** @var array $notifications */
        $notifications = $aggregateMetricNotificationModel->findBy('aggregate_metric_spec_id', $aggregateMetricSpecDao->getAggregateMetricSpecId());
        $response = array();
        /** @var Tracker_AggregateMetricNotificationDao $notification */
        foreach ($notifications as $notification) {
            $response[] = array(
                'notification' => $notification,
                'users' => $aggregateMetricNotificationModel->getAllNotifiedUsers($notification),
            );
        }

        return $response;
    }
}
