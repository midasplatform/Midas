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

/** Feed Model Base */
abstract class FeedModelBase extends AppModel
{
    /** Constructor. */
    public function __construct()
    {
        parent::__construct();
        $this->_name = 'feed';
        $this->_key = 'feed_id';
        $this->_components = array('Sortdao');
        $this->_mainData = array(
            'feed_id' => array('type' => MIDAS_DATA),
            'date' => array('type' => MIDAS_DATA),
            'user_id' => array('type' => MIDAS_DATA),
            'type' => array('type' => MIDAS_DATA),
            'resource' => array('type' => MIDAS_DATA),
            'communities' => array(
                'type' => MIDAS_MANY_TO_MANY,
                'model' => 'Community',
                'table' => 'feed2community',
                'parent_column' => 'feed_id',
                'child_column' => 'community_id',
            ),
            'user' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'User',
                'parent_column' => 'user_id',
                'child_column' => 'user_id',
            ),
            'feedpolicygroup' => array(
                'type' => MIDAS_ONE_TO_MANY,
                'model' => 'Feedpolicygroup',
                'parent_column' => 'feed_id',
                'child_column' => 'feed_id',
            ),
            'feedpolicyuser' => array(
                'type' => MIDAS_ONE_TO_MANY,
                'model' => 'Feedpolicyuser',
                'parent_column' => 'feed_id',
                'child_column' => 'feed_id',
            ),
        );
        $this->initialize(); // required
    }

    /** get Feeds */
    abstract protected function getFeeds(
        $loggedUserDao,
        $userDao = null,
        $communityDao = null,
        $policy = 0,
        $limit = 20
    );

    /** Add a community. */
    abstract protected function addCommunity($feed, $community);

    /** Check policy. */
    abstract public function policyCheck($feedDao, $userDao = null, $policy = 0);

    /**
     * Get feeds (filtered by policies).
     *
     * @return array feed DAOs
     */
    public function getGlobalFeeds($loggedUserDao, $policy = 0, $limit = 20)
    {
        return $this->getFeeds($loggedUserDao, null, null, $policy, $limit);
    }

    /**
     * Get feeds by user (filtered by policies).
     *
     * @return array feed DAOs
     */
    public function getFeedsByUser($loggedUserDao, $userDao, $policy = 0, $limit = 20)
    {
        return $this->getFeeds($loggedUserDao, $userDao, null, $policy, $limit);
    }

    /**
     * Get feeds by community (filtered by policies).
     *
     * @return array feed DAOs
     */
    public function getFeedsByCommunity($loggedUserDao, $communityDao, $policy = 0, $limit = 20)
    {
        return $this->getFeeds($loggedUserDao, null, $communityDao, $policy, $limit);
    }

    /**
     * Create a feed.
     *
     * @return FeedDao
     */
    public function createFeed($userDao, $type, $resource, $communityDao = null)
    {
        if (!$userDao instanceof UserDao && !is_numeric($type) && !is_object($resource)
        ) {
            throw new Zend_Exception('Error in parameters, userdao, type, and resource.');
        }

        /** @var FeedDao $feed */
        $feed = MidasLoader::newDao('FeedDao');
        $feed->setUserId($userDao->getKey());
        $feed->setType($type);
        $feed->setDate(date('Y-m-d H:i:s'));
        switch ($type) {
            case MIDAS_FEED_CREATE_COMMUNITY:
            case MIDAS_FEED_UPDATE_COMMUNITY:
                if (!$resource instanceof CommunityDao) {
                    throw new Zend_Exception('Error in parameter resource, expecting CommunityDao, type:'.$type);
                }
                $feed->setResource($resource->getKey());
                break;
            case MIDAS_FEED_COMMUNITY_INVITATION:
                if (!$resource instanceof CommunityInvitationDao) {
                    throw new Zend_Exception(
                        'Error in parameter resource, expecting CommunityInvitationDao, type:'.$type
                    );
                }
                $feed->setResource($resource->getKey());
                break;
            case MIDAS_FEED_CREATE_FOLDER:
                if (!$resource instanceof FolderDao) {
                    throw new Zend_Exception('Error in parameter resource, expecting FolderDao, type:'.$type);
                }
                $feed->setResource($resource->getKey());
                break;
            case MIDAS_FEED_CREATE_LINK_ITEM:
            case MIDAS_FEED_CREATE_ITEM:
                if (!$resource instanceof ItemDao) {
                    throw new Zend_Exception('Error in parameter resource, expecting ItemDao, type:'.$type);
                }
                $feed->setResource($resource->getKey());
                break;
            case MIDAS_FEED_CREATE_REVISION:
                if (!$resource instanceof ItemRevisionDao) {
                    throw new Zend_Exception('Error in parameter resource, expecting ItemRevisionDao, type:'.$type);
                }
                $feed->setResource($resource->getKey());
                break;
            case MIDAS_FEED_CREATE_USER:
                if (!$resource instanceof UserDao) {
                    throw new Zend_Exception('Error in parameter resource, expecting UserDao, type:'.$type);
                }
                $feed->setResource($resource->getKey());
                break;
            case MIDAS_FEED_DELETE_COMMUNITY:
            case MIDAS_FEED_DELETE_FOLDER:
            case MIDAS_FEED_DELETE_ITEM:
                if (!is_string($resource)) {
                    throw new Zend_Exception('Error in parameter resource, expecting string, type:'.$type);
                }
                $feed->setResource($resource);
                break;
            default:
                throw new Zend_Exception('Unable to find an expected type of feed');
                break;
        }
        $this->save($feed);

        if ($communityDao instanceof CommunityDao) {
            $this->addCommunity($feed, $communityDao);
            $feed->community = $communityDao;
        }

        return $feed;
    }
}
