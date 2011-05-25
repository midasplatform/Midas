<?php
require_once dirname(__FILE__) . '/../../DatabaseTestCase.php';
class FeedModelTest extends DatabaseTestCase
  {

  public function setUp()
    {
    $this->setupDatabase(array('default'));
    $this->_models=array(
      'Feed','Community'
    );
    $this->_daos=array();
    parent::setUp();
    }

  public function testGetGlobalFeeds()
    {
    $usersFile=$this->loadData('User','default');

    $communitiesFile=$this->Feed->getGlobalFeeds($usersFile [0],1);
    $this->assertEquals(2,count($communitiesFile));

    $communitiesFile=$this->Feed->getGlobalFeeds($usersFile [0],2);
    $this->assertEquals(1,count($communitiesFile));
    }

  public function testGetFeedsByUser()
    {
    $usersFile=$this->loadData('User','default');

    $communitiesFile=$this->Feed->getFeedsByUser($usersFile [0],$usersFile [0],1);
    $this->assertEquals(2,count($communitiesFile));

    $communitiesFile=$this->Feed->getFeedsByUser($usersFile [0],$usersFile [0],2);
    $this->assertEquals(1,count($communitiesFile));
    }

  public function testGetFeedsByCommunity()
    {
    $communityFile=$this->loadData('Community','default');
    $usersFile=$this->loadData('User','default');

    $communitiesFile=$this->Feed->getFeedsByCommunity($usersFile [0],$communityFile [0],1);
    $this->assertEquals(1,count($communitiesFile));
    }

  public function testCreateFeed()
    {
    $usersFile=$this->loadData('User','default');
    $communityFile=$this->loadData('Community','default');
    $folderFile=$this->loadData('Folder','default');
    $itemFile=$this->loadData('Item','default');
    $feed=$this->Feed->createFeed($usersFile [0],MIDAS_FEED_CREATE_COMMUNITY,$communityFile[0]);
    $this->assertEquals(true,$feed->saved);
    $feed=$this->Feed->createFeed($usersFile [0],MIDAS_FEED_CREATE_FOLDER,$folderFile[0]);
    $this->assertEquals(true,$feed->saved);
    $feed=$this->Feed->createFeed($usersFile [0],MIDAS_FEED_CREATE_ITEM,$itemFile[0]);
    $this->assertEquals(true,$feed->saved);
    $feed=$this->Feed->createFeed($usersFile [0],MIDAS_FEED_CREATE_USER,$usersFile [0]);
    $this->assertEquals(true,$feed->saved);
    $this->Feed->addCommunity($feed,$communityFile [0]);
    $communities=$feed->getCommunities();
    if(($communities[0]->getKey() != $communityFile[0]->getKey()))
      {
      $this->fail("Unable to add dao");
      }
    }
  }
