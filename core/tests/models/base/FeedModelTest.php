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
/** FeedModelTest*/
class FeedModelTest extends DatabaseTestCase
  {
  /** init test*/
  public function setUp()
    {
    $this->setupDatabase(array('default'));
    $this->_models = array(
      'Feed', 'Community'
    );
    $this->_daos = array();
    parent::setUp();
    }

  /** testGetGlobalFeeds */
  public function testGetGlobalFeeds()
    {
    $usersFile = $this->loadData('User', 'default');

    $communitiesFile = $this->Feed->getGlobalFeeds($usersFile[0], 1);
    $this->assertEquals(2, count($communitiesFile));

    $communitiesFile = $this->Feed->getGlobalFeeds($usersFile[0], 2);
    $this->assertEquals(1, count($communitiesFile));
    }

  /** testGetFeedsByUser*/
  public function testGetFeedsByUser()
    {
    $usersFile = $this->loadData('User', 'default');

    $communitiesFile = $this->Feed->getFeedsByUser($usersFile[0], $usersFile[0], 1);
    $this->assertEquals(2, count($communitiesFile));

    $communitiesFile = $this->Feed->getFeedsByUser($usersFile[0], $usersFile[0], 2);
    $this->assertEquals(1, count($communitiesFile));
    }

  /** testGetFeedsByCommunity*/
  public function testGetFeedsByCommunity()
    {
    $communityFile = $this->loadData('Community', 'default');
    $usersFile = $this->loadData('User', 'default');

    $communitiesFile = $this->Feed->getFeedsByCommunity($usersFile[0], $communityFile[0], 1);
    $this->assertEquals(1, count($communitiesFile));
    }

  /** testCreateFeed*/
  public function testCreateFeed()
    {
    $usersFile = $this->loadData('User', 'default');
    $communityFile = $this->loadData('Community', 'default');
    $folderFile = $this->loadData('Folder', 'default');
    $itemFile = $this->loadData('Item', 'default');
    $feed = $this->Feed->createFeed($usersFile[0], MIDAS_FEED_CREATE_COMMUNITY, $communityFile[0]);
    $this->assertEquals(true, $feed->saved);
    $feed = $this->Feed->createFeed($usersFile [0], MIDAS_FEED_CREATE_FOLDER, $folderFile[0]);
    $this->assertEquals(true, $feed->saved);
    $feed = $this->Feed->createFeed($usersFile [0], MIDAS_FEED_CREATE_ITEM, $itemFile[0]);
    $this->assertEquals(true, $feed->saved);
    $feed = $this->Feed->createFeed($usersFile [0], MIDAS_FEED_CREATE_USER, $usersFile[0]);
    $this->assertEquals(true, $feed->saved);
    $this->Feed->addCommunity($feed, $communityFile [0]);
    $communities = $feed->getCommunities();
    if(($communities[0]->getKey() != $communityFile[0]->getKey()))
      {
      $this->fail("Unable to add dao");
      }
    }

  /** test Feed::getFeedByResourceAndType */
  public function testGetFeedByResourceAndType()
    {
    $usersFile = $this->loadData('User', 'default');
    $feed = $this->Feed->getFeedByResourceAndType(array(MIDAS_FEED_CREATE_USER), $usersFile[0]);
    $this->assertEquals(count($feed), 1);
    $this->assertEquals($feed[0]->user_id, $usersFile[0]->getKey());
    $this->assertEquals($feed[0]->type, MIDAS_FEED_CREATE_USER);
    $this->assertEquals($feed[0]->ressource, (string)$usersFile[0]->getKey());
    }
  }
