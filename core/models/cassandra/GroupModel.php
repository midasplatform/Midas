<?php
require_once BASE_PATH.'/core/models/base/GroupModelBase.php';

/**
 * \class GroupModel
 * \brief Cassandra Model
 */
class GroupModel extends GroupModelBase
{
  /** Add an user to a group
   * @return void
   *  */
  function addUser($group,$user)
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
    
    $column_family->insert($group->getGroupId(),$data);  
    } // end function addUser
    
}  // end class
?>
