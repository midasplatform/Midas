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

/**
 * Producer Model Base
 */
abstract class Tracker_ProducerModelBase extends Tracker_AppModel
  {
  /** constructor*/
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'tracker_producer';
    $this->_key = 'producer_id';
    $this->_mainData = array(
        'producer_id' => array('type' => MIDAS_DATA),
        'community_id' => array('type' => MIDAS_DATA),
        'repository' => array('type' => MIDAS_DATA),
        'revision_url' => array('type' => MIDAS_DATA),
        'executable_name' => array('type' => MIDAS_DATA),
        'display_name' => array('type' => MIDAS_DATA),
        'description' => array('type' => MIDAS_DATA),
        'community' => array('type' => MIDAS_MANY_TO_ONE,
                             'model' => 'Community',
                             'parent_column' => 'community_id',
                             'child_column' => 'community_id'),
        'trends' => array('type' => MIDAS_ONE_TO_MANY,
                          'model' => 'Trend',
                          'module' => $this->moduleName,
                          'parent_column' => 'producer_id',
                          'child_column' => 'producer_id')
      );
    $this->initialize();
    }

  abstract public function getByCommunityId($communityId);
  abstract public function getByCommunityIdAndName($communityId, $displayName);

  /**
   * If the producer with the given displayName and communityId exists, returns it.
   * If not, it will create it and return it.
   */
  public function createIfNeeded($communityId, $displayName)
    {
    $producer = $this->getByCommunityIdAndName($communityId, $displayName);
    if(!$producer)
      {
      $producer = MidasLoader::newDao('ProducerDao', $this->moduleName);
      $producer->setCommunityId($communityId);
      $producer->setDisplayName($displayName);
      $producer->setDescription('');
      $producer->setExecutableName('');
      $producer->setRepository('');
      $producer->setRevisionUrl('');
      $this->save($producer);
      }
    return $producer;
    }

  /**
   * Delete the producer (deletes all related trends as well)
   */
  public function delete($producer)
    {
    $trendModel = MidasLoader::loadModel('Trend', $this->moduleName);
    $trends = $producer->getTrends();
    foreach($trends as $trend)
      {
      $trendModel->delete($trend);
      }
    parent::delete($producer);
    }
  }
