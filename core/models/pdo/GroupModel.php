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
    
    $community = $group->getCommunity();
    $groupMember = $community->getMemberGroup();
    if($groupMember->getKey() != $group->getKey())
      {
      $this->addUser($groupMember, $user);
      }
    
    $this->database->link('users', $group, $user);
    } // end function addItem
    
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
