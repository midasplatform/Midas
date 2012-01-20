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
 *  UserModel
 *  Pdo Model
 */
class GroupModel  extends GroupModelBase
{

  /** Get a groups by Community */
  function findByCommunity($communityDao)
    {
    $rowset = $this->database->fetchAll($this->database->select()->where('community_id = ?', $communityDao->getKey()));
    $result = array();
    foreach($rowset as $row)
      {
      $result[] = $this->initDao(ucfirst($this->_name), $row);
      }
    return $result;
    } // end findByCommunity()

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

    if($group->getKey() != MIDAS_GROUP_SERVER_KEY)
      {
      $community = $group->getCommunity();
      $groupMember = $community->getMemberGroup();
      if($groupMember->getKey() != $group->getKey())
        {
        $this->addUser($groupMember, $user);
        }
      }

    $this->database->link('users', $group, $user);
    } // end function addItem

  /**
   * Remove an user from a group. If you remove them from the "members" group for
   * a community, it removes them from all of that community's groups as well.
   */
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

    $community = $group->getCommunity();
    $groupMember = $community->getMemberGroup();
    if($groupMember->getKey() == $group->getKey())
      {
      $communityGroups = $community->getGroups();
      foreach($communityGroups as $cgroup)
        {
        if($cgroup->getKey() != $groupMember->getKey())
          {
          $this->removeUser($cgroup, $user);
          }
        }
      }

    $this->database->removeLink('users', $group, $user);
    } // end function removeUser


  /** Return a list of group corresponding to the search */
  function getGroupFromSearch($search, $limit = 14)
    {
    $groups = array();

    $sql = $this->database->select();
    $sql->from(array('g' => 'group'));
    $sql->where('g.name LIKE ?', '%'.$search.'%');
    $sql->limit($limit);
    $sql->order(array('g.name ASC'));
    $rowset = $this->database->fetchAll($sql);
    $return = array();
    foreach($rowset as $row)
      {
      $tmpDao = $this->initDao('Group', $row);
      $return[] = $tmpDao;
      unset($tmpDao);
      }
    return $return;
    } // end getCommunitiesFromSearch()
}// end class
?>
