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

/** FeedDaoTest */
class Core_FeedDaoTest extends DatabaseTestCase
{
    /** init tests */
    public function setUp()
    {
        $this->setupDatabase(array());
        $this->_models = array('Feed');
        $this->_daos = array('Feed', 'Community', "Item", 'User', "ItemRevision");
        parent::setUp();
    }

    /** testGetResource */
    public function testGetResource()
    {
        $feeds = $this->loadData('Feed', 'default');
        foreach ($feeds as $feed) {
            $feedDao = $this->Feed->load($feed->getKey());
            if (!$feedDao instanceof FeedDao) {
                $this->fail("Should be a FeedDao");
            }
            switch ($feedDao->getType()) {
                case MIDAS_FEED_CREATE_COMMUNITY:
                case MIDAS_FEED_UPDATE_COMMUNITY:
                    if (!$feedDao->getResource() instanceof CommunityDao) {
                        $this->fail("Error Dao");
                    }
                    break;
                case MIDAS_FEED_CREATE_FOLDER:
                    if (!$feedDao->getResource() instanceof FolderDao) {
                        $this->fail("Error Dao");
                    }
                    break;
                case MIDAS_FEED_CREATE_ITEM:
                case MIDAS_FEED_CREATE_LINK_ITEM:
                    if (!$feedDao->getResource() instanceof ItemDao) {
                        $this->fail("Error Dao");
                    }
                    break;
                case MIDAS_FEED_CREATE_REVISION:
                    if (!$feedDao->getResource() instanceof ItemRevisionDao) {
                        $this->fail("Error Dao");
                    }
                    break;
                case MIDAS_FEED_CREATE_USER:
                    if (!$feedDao->getResource() instanceof UserDao) {
                        $this->fail("Error Dao");
                    }
                    break;
                case MIDAS_FEED_DELETE_COMMUNITY:
                case MIDAS_FEED_DELETE_FOLDER:
                case MIDAS_FEED_DELETE_ITEM:
                    if (!is_string($feedDao->getResource())) {
                        $this->fail("Error result");
                    }
                    break;
                default:
                    $this->fail("Unable to find resource");
            }
        }
    }
}
