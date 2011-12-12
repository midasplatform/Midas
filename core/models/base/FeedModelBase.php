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

/** Feed Model Base */
abstract class FeedModelBase extends AppModel
{
  /** Constructor*/
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
      'ressource' => array('type' => MIDAS_DATA),
      'communities' =>  array('type' => MIDAS_MANY_TO_MANY, 'model' => 'Community', 'table' => 'feed2community', 'parent_column' => 'feed_id', 'child_column' => 'community_id'),
      'user' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'User', 'parent_column' => 'user_id', 'child_column' => 'user_id'),
      'feedpolicygroup' =>  array('type' => MIDAS_ONE_TO_MANY, 'model' => 'Feedpolicygroup', 'parent_column' => 'feed_id', 'child_column' => 'feed_id'),
      'feedpolicyuser' =>  array('type' => MIDAS_ONE_TO_MANY, 'model' => 'Feedpolicyuser', 'parent_column' => 'feed_id', 'child_column' => 'feed_id'),
      );
    $this->initialize(); // required
    } // end __construct()

  /** get Feeds*/
  protected abstract function getFeeds($loggedUserDao, $userDao = null, $communityDao = null, $policy = 0, $limit = 20);
  /** add a community*/
  protected abstract function addCommunity($feed, $community);
  abstract function policyCheck($feedDao, $userDao = null, $policy = 0);


  /** get feeds (filtered by policies)
   * @return Array of FeedDao */
  function getGlobalFeeds($loggedUserDao, $policy = 0, $limit = 20)
    {
    return $this->getFeeds($loggedUserDao, null, null, $policy, $limit);
    } //end getGlobalFeeds

  /** get feeds by user (filtered by policies)
   * @return Array of FeedDao */
  function getFeedsByUser($loggedUserDao, $userDao, $policy = 0, $limit = 20)
    {
    return $this->getFeeds($loggedUserDao, $userDao, null, $policy, $limit);
    } //end getFeedsByUser

  /** get feeds by community (filtered by policies)
     * @return Array of FeedDao */
  function getFeedsByCommunity($loggedUserDao, $communityDao, $policy = 0, $limit = 20)
    {
    return $this->getFeeds($loggedUserDao, null, $communityDao, $policy, $limit);
    } //end getFeedsByCommunity

  /** Create a feed
   * @return FeedDao */
  function createFeed($userDao, $type, $ressource, $communityDao = null)
    {
    if(!$userDao instanceof UserDao && !is_numeric($type) && !is_object($ressource))
      {
      throw new Zend_Exception("Error parameters.");
      }
    $this->loadDaoClass('FeedDao');
    $feed = new FeedDao();
    $feed->setUserId($userDao->getKey());
    $feed->setType($type);
    $feed->setDate(date('c'));
    switch($type)
      {
      case MIDAS_FEED_CREATE_COMMUNITY:
      case MIDAS_FEED_UPDATE_COMMUNITY:
        if(!$ressource instanceof CommunityDao)
          {
          throw new Zend_Exception("Error parameter ressource, type:".$type);
          }
        $feed->setRessource($ressource->getKey());
        break;
      case MIDAS_FEED_COMMUNITY_INVITATION:
        if(!$ressource instanceof CommunityInvitationDao)
          {
          throw new Zend_Exception("Error parameter ressource, type:".$type);
          }
        $feed->setRessource($ressource->getKey());
        break;
      case MIDAS_FEED_CREATE_FOLDER:
        if(!$ressource instanceof FolderDao)
          {
          throw new Zend_Exception("Error parameter ressource, type:".$type);
          }
        $feed->setRessource($ressource->getKey());
        break;
      case MIDAS_FEED_CREATE_LINK_ITEM:
      case MIDAS_FEED_CREATE_ITEM:
        if(!$ressource instanceof ItemDao)
          {
          throw new Zend_Exception("Error parameter ressource, type:".$type);
          }
        $feed->setRessource($ressource->getKey());
        break;
      case MIDAS_FEED_CREATE_REVISION:
        if(!$ressource instanceof ItemRevisionDao)
          {
          throw new Zend_Exception("Error parameter ressource, type:".$type);
          }
        $feed->setRessource($ressource->getKey());
        break;
      case MIDAS_FEED_CREATE_USER:
        if(!$ressource instanceof UserDao)
          {
          throw new Zend_Exception("Error parameter ressource, type:".$type);
          }
        $feed->setRessource($ressource->getKey());
        break;
      case MIDAS_FEED_DELETE_COMMUNITY:
      case MIDAS_FEED_DELETE_FOLDER:
      case MIDAS_FEED_DELETE_ITEM:
        if(!is_string($ressource))
          {
          throw new Zend_Exception("Error parameter ressource, type:".$type);
          }
        $feed->setRessource($ressource);
        break;
      default:
        throw new Zend_Exception("Unable to defined the type of feed");
        break;
      }
    $this->save($feed);

    if($communityDao instanceof CommunityDao)
      {
      $this->addCommunity($feed, $communityDao);
      $feed->community = $communityDao;
      }
    return $feed;
    } // end createFeed()

} // end class FeedModelBase