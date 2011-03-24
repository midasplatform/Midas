<?php
/**
 * \class GroupModel
 * \brief Cassandra Model
 */
class GroupModel extends MIDASGroupModel
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
    
    echo $group->getGroupId()."<br>";
    echo $user->getUserId()."<br>"; 
    
    $column_family = new ColumnFamily($this->database->getDB(), 'group');
    
    $data = array();
    $userarray = array();
    $userarray[$user->getUserId()] = 1;
    $data['users'] = $userarray;
    
    $column_family->insert($group->getGroupId(),$data);     
      
    exit();  
      
      
    } // end function addUser
    
}  // end class
?>
