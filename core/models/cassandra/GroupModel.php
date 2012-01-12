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

require_once BASE_PATH.'/core/models/base/GroupModelBase.php';

/**
 * \class GroupModel
 * \brief Cassandra Model
 */
class GroupModel extends GroupModelBase
{

  /** Get a group by id */
  function getByGroup_id($groupid)
    {
    try
      {
      $group = new ColumnFamily($this->database->getDB(), 'group');
      $grouparray = $group->get($groupid);

      // Add the user_id
      $grouparray[$this->_key] = $groupid;
      $dao = $this->initDao('Group', $grouparray);
      }
    catch(cassandra_NotFoundException $e)
      {
      return false;
      }
    catch(Exception $e)
      {
      throw new Zend_Exception($e);
      }
    return $dao;
    } // end getByGroup_id()


  /** Add an user to a group
   * @return void
   *  */
  function addUser($group, $user)
    {
    if(!$group instanceof GroupDao)
      {
      throw new Zend_Exception("Should be a group.");
      }
    if(!$user instanceof UserDao)
      {
      throw new Zend_Exception("Should be an user.");
      }

    $column_family = new ColumnFamily($this->database->getDB(), 'group');
    $data = array();
    $column = 'user_'.$user->getUserId();
    $data[$column] = date('c');

    $column_family->insert($group->getGroupId(), $data);
    } // end function addUser

  /** Get a groups by Community */
  function findByCommunity($communityDao)
    {
    if(!$communityDao instanceof CommunityDaom)
      {
      throw new Zend_Exception("Should be a community.");
      }
    throw new Zend_Exception("findByCommunity not implemented yet");
    /*
    $rowset = $this->database->fetchAll($this->database->select()->where('community_id = ?', $communityDao->getKey()));
    $result = array();
    foreach($rowset as $row)
      {
      $result[] = $this->initDao(ucfirst($this->_name), $row);
      }
    return $result;*/
    } // end findByCommunity()

  /** Remove an user from a group
   * @return void
   *  */
  function removeUser($group, $user)
    {
    if(!$group instanceof GroupDao)
      {
      throw new Zend_Exception("Should be a group.");
      }
    if(!$user instanceof UserDao)
      {
      throw new Zend_Exception("Should be an user.");
      }
    $column_family = new ColumnFamily($this->database->getDB(), 'group');
    $column_family->remove($group->getGroupId(), array('user_'.$user->getUserId()));
    } // end function removeUser

  /** Get Users attached to a group */
  function getUsers($groupid)
    {
    $users = array();
    $usergrouparray = $this->database->getCassandra('group', $groupid, null, "user_", "user_");
    foreach($usergrouparray as $user)
      {
      $users[] = $this->initDao('User', $user);
      }
    return $users;
    } // end getByGroup_id()

}  // end class

