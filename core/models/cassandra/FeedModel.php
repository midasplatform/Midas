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

require_once BASE_PATH.'/core/models/base/FeedModelBase.php';

/**
 * \class FeedModel
 * \brief Cassandra Model
 */
class FeedModel extends FeedModelBase
{

  /** get Feeds*/
  protected function getFeeds($loggedUserDao, $userDao = null, $communityDao = null, $policy = 0, $limit = 20)
    {
    if(!isset($limit))
      {
      throw new Zend_Exception("Please set the limit.");
      }
    if($loggedUserDao == null)
      {
      $userId = 0; // anonymous
      }
    else if(!$loggedUserDao instanceof UserDao)
      {
      throw new Zend_Exception("Should be an user.");
      }
    else
      {
      $userId = $loggedUserDao->getUserId();
      }

    if($userDao != null && !$userDao instanceof UserDao)
      {
      throw new Zend_Exception("Should be an user.");
      }

    if($communityDao != null && !$communityDao instanceof CommunityDao)
      {
      throw new Zend_Exception("Should be a community.");
      }

    // Get the groups from the user
    $usergrouparray = $this->database->getCassandra('user', $userId, null, "group_", "group_"); // need to get only the group_* columns
    $groupids[] = MIDAS_GROUP_ANONYMOUS_KEY;


    // If we ask for the feeds for a user
    if($userDao)
      {
      $userid = $userDao->getUserId();
      $userfeed = $this->database->getCassandra('userfeed', $userid);

      $userfeedpolicyarray = array();
      $groupfeedpolicyarray = array();

      foreach($userfeed as $feed => $values)
        {
        // Groups
        $groups = array();
        foreach($groupids as $groupid)
          {
          $groups['group_'.$groupid] = 0;
          }

        foreach(array_intersect_key($values, $groups) as $group => $policy)
          {
          if(!isset($groupfeedpolicyarray[$feed])
             || $groupfeedpolicyarray[$feed][substr($feed, 5)] < $policy )
            {
            $array[$feed] = $policy;
            $groupfeedpolicyarray[$feed] = $array;
            }
          }

        // User
        if(key_exists('user_'.$userId, $values))
          {
          $userfeedpolicyarray[$feed] = $values;
          }
        }

      }
    else if($communityDao)
      {
      $communityid = $communityDao->getCommunityId();
      $communityfeed = $this->database->getCassandra('communityfeed', $communityid);

      $userfeedpolicyarray = array();
      $groupfeedpolicyarray = array();

      foreach($communityfeed as $feed => $values)
        {
        // Groups
        $groups = array();
        foreach($groupids as $groupid)
          {
          $groups['group_'.$groupid] = 0;
          }

        foreach(array_intersect_key($values, $groups) as $group => $policy)
          {
          if(!isset($groupfeedpolicyarray[$feed])
             || $groupfeedpolicyarray[$feed][substr($feed, 5)] < $policy )
            {
            $array[$feed] = $policy;
            $groupfeedpolicyarray[$feed] = $array;
            }
          }

        // User
        if(key_exists('user_'.$userId, $values))
          {
          $userfeedpolicyarray[$feed] = $values;
          }
        }
      }
    else
      {
      // Select from the UserFeedPolicy
      $userfeedpolicyarray = $this->database->getCassandra('userfeedpolicy', $userId);

      // Select from the GroupFeedPolicy for the groups
      // multiget
      $groupfeedpolicyarray = $this->database->multigetCassandra('groupfeedpolicy', $groupids); // need to get only the group_* columns
      }

    $rowsetAnalysed = array();
    // Start with the users
    foreach($userfeedpolicyarray as $key => $row)
      {
      $feed_id = substr($key, 5);
      $feedrow = $this->database->getCassandra('feed', $feed_id);
      $feedrow['feed_id'] = $feed_id;
      $dao =  $this->initDao('Feed', $feedrow);
      $dao->policy = $row;
      $rowsetAnalysed[$feed_id] = $dao;
      unset($dao);
      }

    // Then the groups
    foreach($groupfeedpolicyarray as $key => $grouprow)
      {
      foreach($grouprow as $feed_id => $policy)
        {
        $feed_id = substr($feed_id, 5);

        $feedrow = $this->database->getCassandra('feed', $feed_id);
        $feedrow['feed_id'] = $feed_id;

        if(isset($rowsetAnalysed[$feed_id]))
          {
          $dao = $rowsetAnalysed[$feed_id];
          if($dao->policy < $policy)
            {
            $rowsetAnalysed[$feed_id]->policy = $policy;
            }
          }
        else
          {
          $dao = $this->initDao('Feed', $feedrow);
          $dao->policy = $policy;
          $rowsetAnalysed[$feed_id] = $dao;
          unset($dao);
          }
        }
      }

    $this->Component->Sortdao->field = 'date';
    $this->Component->Sortdao->order = 'asc';
    usort($rowsetAnalysed, array($this->Component->Sortdao, 'sortByDate'));
    return $rowsetAnalysed;
    }

  /** Add a community to a feed
   * @return void */
  function addCommunity($feed, $community)
    {
    if(!$community instanceof CommunityDao)
      {
      throw new Zend_Exception("Should be a community.");
      }
    if(!$feed instanceof FeedDao)
      {
      throw new Zend_Exception("Should be an feed.");
      }

    $feedid = $feed->getFeedId();
    $communityid = $community->getCommunityId();
    $column = 'feed_'.$feedid;

    $dataarray = array();
    $dataarray[$column] = array(); // supercolumn to be filled out when we set the policies
    $dataarray[$column]['na'] = 'na';

    $column_family = new ColumnFamily($this->database->getDB(), 'communityfeed');
    $column_family->insert($communityid, $dataarray);
    } // end addCommunity


} // end class
?>
