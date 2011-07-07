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
/** FeedDaoTest */
class FeedDaoTest extends DatabaseTestCase
  {
  /** init tests */
  public function setUp()
    {
    $this->setupDatabase(array(
    ));
    $this->_models = array(
      'Feed'
    );
    $this->_daos = array(
      'Feed', 'Community', "Item", 'User', "ItemRevision"
    );
    parent::setUp();
    }

  /** testGetRessource*/ 
  public function testGetRessource()
    {
    $feeds = $this->loadData('Feed', 'default');
    foreach($feeds as $feed)
      {
      $feedDao = $this->Feed->load($feed->getKey());
      if(!$feedDao instanceof FeedDao)
        {
        $this->fail("Should be a FeedDao");
        }
      switch($feedDao->getType())
        {
        case MIDAS_FEED_CREATE_COMMUNITY :
        case MIDAS_FEED_UPDATE_COMMUNITY :
          if(!$feedDao->getRessource() instanceof CommunityDao)
            {
            $this->fail("Error Dao");
            }
          break;
        case MIDAS_FEED_CREATE_FOLDER :
          if(!$feedDao->getRessource() instanceof FolderDao)
            {
            $this->fail("Error Dao");
            }
          break;
        case MIDAS_FEED_CREATE_ITEM :
        case MIDAS_FEED_CREATE_LINK_ITEM :
          if(!$feedDao->getRessource() instanceof ItemDao)
            {
            $this->fail("Error Dao");
            }
          break;
        case MIDAS_FEED_CREATE_REVISION :
          if(!$feedDao->getRessource() instanceof ItemRevisionDao)
            {
            $this->fail("Error Dao");
            }
          break;
        case MIDAS_FEED_CREATE_USER :
          if(!$feedDao->getRessource() instanceof UserDao)
            {
            $this->fail("Error Dao");
            }
          break;
        case MIDAS_FEED_DELETE_COMMUNITY :
        case MIDAS_FEED_DELETE_FOLDER :
        case MIDAS_FEED_DELETE_ITEM :
          if(!is_string($feedDao->getRessource()))
            {
            $this->fail("Error result");
            }
          break;
        default :
          $this->fail("Unable to find resource");
        }
      }
    }
  }