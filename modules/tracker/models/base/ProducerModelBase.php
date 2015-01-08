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

/**
 * Producer Model Base
 */
abstract class Tracker_ProducerModelBase extends Tracker_AppModel
{
    /** constructor */
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
            'community' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'Community',
                'parent_column' => 'community_id',
                'child_column' => 'community_id',
            ),
            'trends' => array(
                'type' => MIDAS_ONE_TO_MANY,
                'model' => 'Trend',
                'module' => $this->moduleName,
                'parent_column' => 'producer_id',
                'child_column' => 'producer_id',
            ),
        );
        $this->initialize();
    }

    /** Get by community id */
    abstract public function getByCommunityId($communityId);

    /**  Get by community id and name */
    abstract public function getByCommunityIdAndName($communityId, $displayName);

    /**
     * If the producer with the given displayName and communityId exists, returns it.
     * If not, it will create it and return it.
     */
    public function createIfNeeded($communityId, $displayName)
    {
        $producer = $this->getByCommunityIdAndName($communityId, $displayName);
        if (!$producer) {
            /** @var Tracker_ProducerDao $producer */
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
        /** @var Tracker_ScalarModel $trendModel */
        $trendModel = MidasLoader::loadModel('Trend', $this->moduleName);
        $trends = $producer->getTrends();
        foreach ($trends as $trend) {
            $trendModel->delete($trend);
        }
        parent::delete($producer);
    }
}
