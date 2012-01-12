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

require_once BASE_PATH.'/core/models/base/CommunityModelBase.php';

/**
 * \class FeedModel
 * \brief Cassandra Model
 */
class CommunityModel extends CommunityModelBase
{
  /** get Public communities */
  function getPublicCommunities($limit = 20)
    {
    if(!is_numeric($limit))
      {
      throw new Zend_Exception("Error parameter.");
      }

    $communities = array();

    // We assume we don't have a lot of communities
    // We get from the table emailuser
    try
      {
      $community = new ColumnFamily($this->database->getDB(), 'community');
      $allcommunities = $community->get_range("", // start
                                              "", // end
                                              $limit // row count
                                              );

      foreach($allcommunities as $key => $com)
        {
        if($com['privacy'] != MIDAS_COMMUNITY_PRIVATE)
          {
          $com[$this->_key] = $key;
          $communities[] = $this->initDao('Community', $com);
          }
        }
      }
    catch(cassandra_NotFoundException $e)
      {
      return $communities;
      }
    catch(Exception $e)
      {
      throw new Zend_Exception($e);
      }
    return $communities;
    }

  /** Get a community by name */
  function getByName($name)
    {
    // We assume we don't have a lot of communities
    // Otherwise we'll do an index table
    try
      {
      $community = new ColumnFamily($this->database->getDB(), 'community');
      $communitiesarray = $community->get_range();

      foreach($communitiesarray as $key => $value)
        {
        if($value['name'] == $name)
          {
          // Add the community_id
          $value[$this->_key] = $key;
          $dao = $this->initDao('Community', $value);
          return $dao;
          }
        }
      }
    catch(cassandra_NotFoundException $e)
      {
      return false;
      }
    catch(Exception $e)
      {
      throw new Zend_Exception($e);
      }
    return false;
    } // end getByName()

} // end class CommunityModel
?>
