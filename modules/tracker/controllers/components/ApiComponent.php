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

/** Component for api methods */
class Tracker_ApiComponent extends AppComponent
{
  /**
   * Helper function for verifying keys in an input array
   */
  private function _checkKeys($keys, $values)
    {
    foreach($keys as $key)
      {
      if(!array_key_exists($key, $values))
        {
        throw new Exception('Parameter '.$key.' must be set.', -1);
        }
      }
    }

  /**
   * Helper function to get the user from token or session authentication
   */
  private function _getUser($args)
    {
    $authComponent = MidasLoader::loadComponent('Authentication', 'api');
    return $authComponent->getUser($args, $this->userSession->Dao);
    }

  /**
   * Associate a result item with a particular scalar value
   * @param scalarId The id of the scalar to associate the item with
   * @param itemId The id of the item to associate with the scalar
   * @param label The label describing the nature of the association
   */
  public function itemAssociate($args)
    {
    // TODO
    }

  /**
   * Create a new scalar data point (must have write access to the community)
   * @param communityId The id of the community that owns the producer
   * @param producerDisplayName The display name of the producer
   * @param metricName The metric name that identifies which trend this point belongs to
   * @param producerRevision The repository revision of the producer that produced this value
   * @param submitTime The submit timestamp. Must be parseable with PHP strtotime().
   * @param value The value of the scalar
   * @param configItemId (Optional) If this value pertains to a specific configuration item, pass its id here
   * @param testDatasetId (Optional) If this value pertains to a specific test dataset, pass its id here
   * @param truthDatasetId (Optional) If this value pertains to a specific ground truth dataset, pass its id here
   * @return The scalar dao that was created
   */
  public function scalarAdd($args)
    {
    $communityModel = MidasLoader::loadModel('Community');
    $itemModel = MidasLoader::loadModel('Item');
    $this->_checkKeys(array('communityId', 'producerDisplayName', 'metricName', 'value', 'producerRevision', 'submitTime'), $args);
    $user = $this->_getUser($args);

    $community = $communityModel->load($args['communityId']);
    if(!$community || !$communityModel->policyCheck($community, $user, MIDAS_POLICY_WRITE))
      {
      throw new Exception('Write permission required on community', 403);
      }

    $producerDisplayName = trim($args['producerDisplayName']);
    if($producerDisplayName == '')
      {
      throw new Exception('Producer display name must not be empty', -1);
      }

    $producerModel = MidasLoader::loadModel('Producer', 'tracker');
    $producer = $producerModel->createIfNeeded($community->getKey(), $producerDisplayName);

    $metricName = trim($args['metricName']);
    if($metricName == '')
      {
      throw new Exception('Metric name must not be empty', -1);
      }

    list($configItemId, $testDatasetId, $truthDatasetId) = array(null, null, null);
    if(isset($args['configItemId']))
      {
      $configItemId = $args['configItemId'];
      $configItem = $itemModel->load($args['configItemId']);
      if(!$configItem || !$itemModel->policyCheck($configItem, $user, MIDAS_POLICY_READ))
        {
        throw new Exception('Read permission required on config item', 403);
        }
      }
    if(isset($args['testDatasetId']))
      {
      $truthDatasetId = $args['testDatasetId'];
      $testDatasetItem = $itemModel->load($args['testDatasetId']);
      if(!$testDatasetItem || !$itemModel->policyCheck($testDatasetItem, $user, MIDAS_POLICY_READ))
        {
        throw new Exception('Read permission required on test dataset item', 403);
        }
      }
    if(isset($args['truthDatasetId']))
      {
      $truthDatasetId = $args['truthDatasetId'];
      $truthDatasetItem = $itemModel->load($args['truthDatasetId']);
      if(!$truthDatasetItem || !$itemModel->policyCheck($truthDatasetItem, $user, MIDAS_POLICY_READ))
        {
        throw new Exception('Read permission required on truth dataset item', 403);
        }
      }

    $trendModel = MidasLoader::loadModel('Trend', 'tracker');
    $trend = $trendModel->createIfNeeded($producer->getKey(), $metricName, $configItemId, $testDatasetId, $truthDatasetId);

    $submitTime = strtotime($args['submitTime']);
    if($submitTime === false)
      {
      throw new Exception('Invalid submitTime value: '.$args['submitTime'], -1);
      }
    $submitTime = date('c', $submitTime);

    $value = (float)$args['value'];

    $producerRevision = trim($args['producerRevision']);

    $scalarModel = MidasLoader::loadModel('Scalar', 'tracker');
    $scalar = $scalarModel->addToTrend($trend, $submitTime, $producerRevision, $value, true);
    return $scalar;
    }
} // end class
