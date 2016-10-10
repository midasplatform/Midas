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
     *                       will be created
     * @param configItemId (Optional) If this value pertains to a specific configuration item, pass its id here
     * @param testDatasetId (Optional) If this value pertains to a specific test dataset, pass its id here
     * @param truthDatasetId (Optional) If this value pertains to a specific ground truth dataset, pass its id here
     * @param unit (Optional) If passed, the unit of the scalar value that identifies which trend this point belongs to
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
     * @param uuid (Optional) A unique identifier for the submission. If none is passed, one will be generated
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
     * Parses a valid associative array from a string containing a json document.
     *
     * @param string jsonString string that should be a json document
     * @return mixed associative array of the parsed json document
     * @throws Exception
     */
    protected function _parseValidJson($jsonString)
    {
        $json = json_decode($jsonString, true);
        $jsonLastError = json_last_error();
        if ($jsonLastError !== JSON_ERROR_NONE) {
            $this->getLogger()->info('The jsonString is invalid JSON: '.$jsonLastError);
        }
        return $json;
    }

    /**
     * Validates a string against a json-schema found at schemaPath.
     *
     * @param string documentString string that should be a json document
     * @param string schemaPath filesystem path to a json-schema document
     * @return bool true if a valid json document parsed from documentString
     * can be validated by the valid json-schema file found at schemaPath,
     * false otherwise
     * @throws Exception
     */
    protected function _validateJson($documentString, $schemaPath)
    {
        $documentJson = $this->_parseValidJson($documentString);
        if ($documentJson === null) {
            $this->getLogger()->info('The document is invalid JSON.');
            return false;
        }
        $schemaString = file_get_contents($schemaPath);
        $schemaJson = $this->_parseValidJson($schemaString);
        if ($schemaJson === null) {
            $this->getLogger()->info('The schema is invalid JSON.');
            return false;
        }
        $refResolver = new JsonSchema\RefResolver(new JsonSchema\Uri\UriRetriever(), new JsonSchema\Uri\UriResolver());
        $schema = $refResolver->resolve('file://'.realpath($schemaPath));
        $validator = new JsonSchema\Validator();
        $validator->check(json_decode($documentString), $schema);
        $valid = $validator->isValid();
        if (!$valid) {
            $this->getLogger()->warn('The supplied document JSON does not validate. Violations:\n');
            foreach ($validator->getErrors() as $error) {
                $this->getLogger()->warn(sprintf("[%s] %s\n", $error['property'], $error['message']));
                echo sprintf("[%s] %s\n", $error['property'], $error['message']);
            }
            return false;
        }
        return $documentJson;
     }

    /**
     * Finds an Item by name, ensuring that the user has read access.
     *
     * @param string name Name of the item
     * @param UserDao userDao User to check permissions for
     * @return ItemDao Found and readable item
     * @throws Exception
     */
    protected function _findReadableItemByName($name, $userDao)
    {
        if ($name === '' || $name === false) {
            throw new Exception('Invalid item', 404);
        }
        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');
        /** @var array $itemDaos */
        $itemDaos = $itemModel->getByName($name);
        if (count($itemDaos) < 1) {
            throw new Exception('Invalid item with name '.$name, 404);
        }
        /** @var ItemDao $itemDao */
        $itemDao = $itemDaos[0];
        if (!$itemModel->policyCheck($itemDao, $userDao, MIDAS_POLICY_READ)) {
            throw new Exception('Read permission on the dataset item required', 403);
        }
        return $itemDao;
    }

    /**
     * Add the values of a submission document to the database.
     *
     * @param string communityName maybe Name of the community associated to the submission
     * @param string producerDisplayName maybe Display name of the producer associated to the submission
     * @throws Exception
     */
    public function submissionAddFromArchive($args)
    {
        // Allow a producer config to be passed?  Probably should so that
        // they can update the producer.
        // TODO do we want these as query params or in the json?  probably in the json.
        $this->_checkKeys(array('communityName', 'producerDisplayName'), $args);
        $userDao = $this->_getUser($args);
        $communityName = $args['communityName'];
        // skipping experimental for now, hopefully this is obsolete
        // //$experimental = isset($args['experimental']) ? $args['experimental'] : false;
        $folderName = $args['folderName'];
        $privacy = $args['privacy'];

        /** @var CommunityModel $communityModel */
        $communityModel = MidasLoader::loadModel('Community');
        /** @var CommunityDao $communityDao */
        $communityDao = $communityModel->getByName($communityName);
        if ($communityDao === false || !$communityModel->policyCheck($communityDao, $userDao, MIDAS_POLICY_WRITE)
        ) {
            throw new Exception(
                "This community doesn't exist or you don't have the permissions.", MIDAS_INVALID_POLICY
            );
        }

        $producerDisplayName = $args['producerDisplayName'];
        if ($producerDisplayName == '') {
            throw new Exception('Producer display name must not be empty', -1);
        }
        /** @var Tracker_ProducerModel $producerModel */
        $producerModel = MidasLoader::loadModel('Producer', 'tracker');
        /** @var Tracker_ProducerDao $producerDao */
        $producerDao = $producerModel->createIfNeeded($communityDao->getKey(), $producerDisplayName);

        // TODO Somehow get the submission document
        // this should come from GCS or somewhere

        // TODO HACK
        $submissionDocumentPath = '/home/vagrant/test_sub.json';
        $submissionDocument = file_get_contents($submissionDocumentPath);
        $schemaPath = BASE_PATH.'/modules/tracker/schema/submission.json';
        $submissionJson = $this->_validateJson($submissionDocument, $schemaPath);
        if ($submissionJson) {
            $uuid = $submissionJson['uuid'];
            $this->getLogger()->info('The supplied submissionDocument JSON for uuid '.$uuid.' is valid.');
            /** @var Tracker_SubmissionModel $submissionModel */
            $submissionModel = MidasLoader::loadModel('Submission', 'tracker');
            /** @var Tracker_SubmissionDao $submissionDao */
            $submissionDao = $submissionModel->getOrCreateSubmission($producerDao, $uuid);
            if ($submissionDao === false) {
                throw new Zend_Exception('The submission does not exist', 403);
            }

            $testDatasetName = $submissionJson['test_dataset'];
            /** @var ItemDao $testDatasetItemDao */
            $testDatasetItemDao = $this->_findReadableItemByName($testDatasetName, $userDao);

            /** @var ItemDao $truthDatasetItemDao */
            $truthDatasetItemDao = null;
            if (array_key_exists('truth_dataset', $submissionJson)) {
                $truthDatasetName = $submissionJson['truth_dataset'];
                $truthDatasetItemDao = $this->_findReadableItemByName($truthDatasetName, $userDao);
            }

            /** @var ItemDao $configItemDao */
            $configItemDao = null;
            if (array_key_exists('config_dataset', $submissionJson)) {
                $configDatasetName = $submissionJson['config_dataset'];
                $configItemDao = $this->_findReadableItemByName($configDatasetName, $userDao);
            }

            $submissionMetrics = array();
            if (array_key_exists('metrics', $submissionJson)) {
                $submissionMetrics = $submissionJson['metrics'];
            }
            /** @var Tracker_TrendModel $trendModel */
            $trendModel = MidasLoader::loadModel('Trend', 'tracker');
            /** @var Tracker_ScalarModel $scalarModel */
            $scalarModel = MidasLoader::loadModel('Scalar', 'tracker');
            /** @var mixed $metric */
            foreach ($submissionMetrics as $metric) {
                if (array_key_exists('unit', $metric)) {
                    $unit = $metric['unit'];
                } else {
                    $unit = false;
                }

                /** @var Tracker_TrendDao $trendDao */
                $trendDao = $trendModel->createIfNeeded(
                    $producerDao->getKey(),
                    $metric['name'],
                    $configItemDao === null ? null : $configItemDao->getKey(),
                    $testDatasetItemDao->getKey(),
                    $truthDatasetItemDao === null ? null : $truthDatasetItemDao->getKey(),
                    $unit
                );
                /** @var Tracker_ScalarDao $scalarDao */
                $scalarDao = $scalarModel->addToTrend($trendDao, $submissionDao, $metric['value']);
            }
        } else {
            throw new Exception('Submission document is invalid', 401);
        }
    }

    /**
     * Validate the producer configuration and submission documents that are tied
     * to a submission, updating the properties of the producer based off of
     * the producer configuration.
     *
     * @param uuid The uuid of the submission to validate documents for
     * @param producerConfig (Optional) JSON object describing the pipeline
     * @param submissionDocument (Optional) JSON object describing the submission
     * @throws Exception
     */
    public function submissionValidate($args)
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

        if (isset($args['producerConfig'])) {
            $producerConfig = $args['producerConfig'];
            $refResolver = new JsonSchema\RefResolver(new JsonSchema\Uri\UriRetriever(), new JsonSchema\Uri\UriResolver());
            $schemaPath = BASE_PATH.'/modules/tracker/schema/producer.json';

            $schema = $refResolver->resolve('file://'.realpath($schemaPath));
            $validator = new JsonSchema\Validator();
            $validator->check(json_decode($producerConfig), $schema);

            if (!$validator->isValid()) {
                $this->getLogger()->warn('The supplied producerConfig JSON for uuid '.$uuid." does not validate. Violations:\n");
                /** @var array $error */
                foreach ($validator->getErrors() as $error) {
                    $this->getLogger()->warn(sprintf("[%s] %s\n", $error['property'], $error['message']));
                }
            } else {
                $this->getLogger()->info('The supplied producerConfig JSON for uuid '.$uuid.' is valid.');

                /** @var Tracker_ProducerModel $producerModel */
                $producerModel = MidasLoader::loadModel('Producer', 'tracker');
                /** @var Tracker_ProducerDao $producerDao */
                $producerDao = $submissionDao->getProducer();
                if (!$producerModel->policyCheck(
                    $producerDao,
                    $user,
                    MIDAS_POLICY_WRITE
                )) {
                    throw new Exception('Write permission on the producer required', 403);
                }
                /** @var stdClass $producerDefinition */
                $producerDefinition = json_decode($producerConfig);
                // Ensure that Producer and Community match.
                if ($producerDefinition->producer !== $producerDao->getDisplayName()) {
                    throw new Exception('Producer schema name must match existing Producer display name', 404);
                }
                if ($producerDefinition->community !== $producerDao->getCommunity()->getName()) {
                    throw new Exception('Producer schema community name must match existing Producer Community name', 404);
                }

                // Save the producer definition to the producer.
                $producerDao->setProducerDefinition($producerConfig);
                // Update top level fields on the producer based on the definition.
                if (isset($producerDefinition->histogram_max_x)) {
                    $producerDao->setHistogramMaxX($producerDefinition->histogram_max_x);
                }
                if (isset($producerDefinition->grid_across_metric_groups)) {
                    $producerDao->setGridAcrossMetricGroups($producerDefinition->grid_across_metric_groups);
                }
                if (isset($producerDefinition->histogram_number_of_bins)) {
                    $producerDao->setHistogramNumberOfBins($producerDefinition->histogram_number_of_bins);
                }
                $producerModel->save($producerDao);

                $defaults = $producerDefinition->defaults;
                if (!isset($defaults)) {
                    // Provide a default for $defaults so the below ?: logic works.
                    $defaults = new stdClass();
                }

                /**
                 * Helper function to populate a metric based on the overall
                 * metrics defaults, overriding any default values with any
                 * specified in the metric itself, populating all properties with
                 * some unassigned (false or null) value if no other value is found.
                 * @param stdClass $metric the metric with specific values
                 * @return array populated metric values
                 */
                $populateMetricValues = function ($metric) use ($defaults) {
                    $populatedMetricUnassigned = array(
                        'abbreviation' => false,
                        'min' => false,
                        'max' => false,
                        'warning' => false,
                        'fail' => false,
                        // Special handling as false is meaningful in this case.
                        'lower_is_better' => null,
                    );
                    $populatedMetric = array();
                    /** @var string $key */
                    /** @var mixed $unassignedValue */
                    foreach ($populatedMetricUnassigned as $key => $unassignedValue) {
                        if (isset($metric->$key)) {
                            $populatedMetric[$key] = $metric->$key;
                        } elseif (isset($defaults->$key)) {
                            $populatedMetric[$key] = $defaults->$key;
                        } else {
                            $populatedMetric[$key] = $unassignedValue;
                        }
                    }
                    if ($populatedMetric['lower_is_better'] === null &&
                        $populatedMetric['warning'] !== false &&
                        $populatedMetric['fail'] !== false) {
                        // We can infer in this case.
                        $populatedMetric['lower_is_better'] =
                            $populatedMetric['warning'] < $populatedMetric['fail'];
                    }

                    return $populatedMetric;
                };

                // Add or update any key metrics and thresholds.
                /** @var Tracker_TrendModel $trendModel */
                $trendModel = MidasLoader::loadModel('Trend', 'tracker');
                /** @var Tracker_TrendThresholdModel $trendThresholdModel */
                $trendThresholdModel = MidasLoader::loadModel('TrendThreshold', 'tracker');
                $keyMetrics = $producerDefinition->key_metrics;
                /** @var stdClass $keyMetric */
                foreach ($keyMetrics as $keyMetric) {
                    // Set any needed trends to be key_metrics.
                    $trendModel->setAggregatableTrendAsKeyMetrics($producerDao, $keyMetric->name);
                    $metricValues = $populateMetricValues($keyMetric);
                    $trendThresholdModel->upsert(
                        $producerDao,
                        $keyMetric->name,
                        $metricValues['abbreviation'],
                        $metricValues['warning'],
                        $metricValues['fail'],
                        $metricValues['min'],
                        $metricValues['max'],
                        $metricValues['lower_is_better']
                    );
                }
                // Add or update any aggregate metrics and thresholds, based on matching
                // the producer and spec.
                $aggregateMetrics = $producerDefinition->aggregate_metrics;
                /** @var Tracker_AggregateMetricSpecModel $aggregateMetricSpecModel */
                $aggregateMetricSpecModel = MidasLoader::loadModel('AggregateMetricSpec', 'tracker');
                /** @var Tracker_AggregateMetricNotificationModel $aggregateMetricNotificationModel */
                $aggregateMetricNotificationModel = MidasLoader::loadModel('AggregateMetricNotification', 'tracker');
                /** @var UserModel $userModel */
                $userModel = MidasLoader::loadModel('User');
                /** @var stdClass $aggregateMetric */
                foreach ($aggregateMetrics as $aggregateMetric) {
                    $metricValues = $populateMetricValues($aggregateMetric);
                    /** @var Tracker_AggregateMetricSpecDao $aggregateMetricSpecDao */
                    $aggregateMetricSpecDao = $aggregateMetricSpecModel->upsert(
                        $producerDao,
                        $aggregateMetric->name,
                        $aggregateMetric->definition,
                        $metricValues['abbreviation'],
                        // Set empty string for description.
                        '',
                        $metricValues['warning'],
                        $metricValues['fail'],
                        $metricValues['min'],
                        $metricValues['max'],
                        $metricValues['lower_is_better']
                    );
                    // Delete any notifications tied to this Aggregate Metric, and create any
                    // as needed.
                    $staleNotifications = $aggregateMetricNotificationModel->findBy('aggregate_metric_spec_id', $aggregateMetricSpecDao->getAggregateMetricSpecId());
                    /** @var Tracker_AggregateMetricNotificationDao $staleNotification */
                    foreach ($staleNotifications as $staleNotification) {
                        $aggregateMetricNotificationModel->delete($staleNotification);
                    }
                    if (isset($aggregateMetric->notifications)) {
                        /** @var stdClass $notification */
                        foreach ($aggregateMetric->notifications as $notification) {
                            /** @var Tracker_AggregateMetricNotificationDao $aggregateMetricNotificationDao */
                            $aggregateMetricNotificationDao = MidasLoader::newDao('AggregateMetricNotificationDao', $this->moduleName);
                            $aggregateMetricNotificationDao->setAggregateMetricSpecId($aggregateMetricSpecDao->getAggregateMetricSpecId());
                            $aggregateMetricNotificationDao->setBranch($notification->branch);
                            $aggregateMetricNotificationDao->setComparison($notification->comparison);
                            $aggregateMetricNotificationDao->setValue($notification->value);
                            $aggregateMetricNotificationModel->save($aggregateMetricNotificationDao);
                            if (isset($notification->emails)) {
                                foreach ($notification->emails as $email) {
                                    // We can only add notifications for valid users.
                                    $userDao = $userModel->getByEmail($email);
                                    if ($userDao !== false) {
                                        $aggregateMetricNotificationModel->createUserNotification($aggregateMetricNotificationDao, $userDao);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if (isset($args['submissionDocument'])) {
            $submissionDocument = $args['submissionDocument'];
            $refResolver = new JsonSchema\RefResolver(new JsonSchema\Uri\UriRetriever(), new JsonSchema\Uri\UriResolver());
            $schemaPath = BASE_PATH.'/modules/tracker/schema/submission.json';

            $schema = $refResolver->resolve('file://'.realpath($schemaPath));
            $validator = new JsonSchema\Validator();
            $validator->check(json_decode($submissionDocument), $schema);

            if (!$validator->isValid()) {
                $this->getLogger()->warn('The supplied submissionDocument JSON for uuid '.$uuid." does not validate. Violations:\n");
                foreach ($validator->getErrors() as $error) {
                    $this->getLogger()->warn(sprintf("[%s] %s\n", $error['property'], $error['message']));
                }
            } else {
                $this->getLogger()->info('The supplied submissionDocument JSON for uuid '.$uuid.' is valid.');

                /** @var Tracker_ProducerModel $producerModel */
                $producerModel = MidasLoader::loadModel('Producer', 'tracker');
                /** @var Tracker_ProducerDao $producerDao */
                $producerDao = $submissionDao->getProducer();
                if (!$producerModel->policyCheck(
                    $producerDao,
                    $user,
                    MIDAS_POLICY_WRITE
                )) {
                    throw new Exception('Write permission on the producer required', 403);
                }
                $submissionDao->setDocument($submissionDocument);
                $submissionModel->save($submissionDao);
            }
        }
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
