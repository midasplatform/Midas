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

/** Producer base model class for the tracker module. */
abstract class Tracker_ProducerModelBase extends Tracker_AppModel
{
    /** Constructor. */
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
            'histogram_max_x' => array('type' => MIDAS_DATA),
            'producer_definition' => array('type' => MIDAS_DATA),
            'community' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'Community',
                'parent_column' => 'community_id',
                'child_column' => 'community_id',
            ),
            'trendgroups' => array(
                'type' => MIDAS_ONE_TO_MANY,
                'model' => 'Trendgroup',
                'module' => $this->moduleName,
                'parent_column' => 'producer_id',
                'child_column' => 'producer_id',
            ),
        );

        $this->initialize();
    }

    /**
     * Return all producers for the given community id.
     *
     * @param int $communityId community id
     * @return array producer DAOs
     */
    abstract public function getByCommunityId($communityId);

    /**
     * Return the producer with the given display name for the given community id.
     *
     * @param int $communityId community id
     * @param string $displayName display name
     * @return false|Tracker_ProducerDao producer DAO or false if no such producer exists
     * @throws Zend_Exception
     */
    abstract public function getByCommunityIdAndName($communityId, $displayName);

    /**
     * Return the producer DAO that matches the given display name and community id if it exists.
     * Otherwise, create the producer DAO.
     *
     * @param int $communityId community id
     * @param string $displayName display name
     * @return Tracker_ProducerDao producer DAO
     */
    public function createIfNeeded($communityId, $displayName)
    {
        $producerDao = $this->getByCommunityIdAndName($communityId, $displayName);

        if ($producerDao === false) {
            /** @var Tracker_ProducerDao $producerDao */
            $producerDao = MidasLoader::newDao('ProducerDao', $this->moduleName);
            $producerDao->setCommunityId($communityId);
            $producerDao->setDisplayName($displayName);
            $producerDao->setDescription('');
            $producerDao->setExecutableName('');
            $producerDao->setRepository('');
            $producerDao->setRevisionUrl('');
            $this->save($producerDao);
        }

        return $producerDao;
    }

    /**
     * Delete the given producer and all associated trends.
     *
     * @param Tracker_ProducerDao $producerDao producer DAO
     */
    public function delete($producerDao)
    {
        /** @var Tracker_TrendgroupModel $trendgroupModel */
        $trendgroupModel = MidasLoader::loadModel('Trendgroup', $this->moduleName);
        $trendgroupDaos = $producerDao->getTrendgroups();

        /** @var Tracker_TrendgroupDao $trendgroupDao */
        foreach ($trendgroupDaos as $trendgroupDao) {
            $trendgroupModel->delete($trendgroupDao);
        }

        parent::delete($producerDao);
    }

    /**
     * Check whether the given policy is valid for the given producer and user.
     *
     * @param Tracker_ProducerDao $producerDao producer DAO
     * @param null|UserDao $userDao user DAO
     * @param int $policy policy
     * @return bool true if the given policy is valid for the given producer and user
     */
    public function policyCheck($producerDao, $userDao = null, $policy = MIDAS_POLICY_READ)
    {
        if (is_null($producerDao) || $producerDao === false) {
            return false;
        }

        /** @var CommunityModel $communityModel */
        $communityModel = MidasLoader::loadModel('Community');
        $communityDao = $producerDao->getCommunity();

        return $communityDao !== false && $communityModel->policyCheck($communityDao, $userDao, $policy);
    }
}
