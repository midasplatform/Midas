<?php
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
