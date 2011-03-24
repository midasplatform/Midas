<?php
require_once BASE_PATH.'/core/models/base/GroupModelBase.php';

/**
 *  UserModel
 *  Pdo Model
 */
class GroupModel  extends GroupModelBase
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
    $this->database->link('users',$group,$user);
    } // end function addItem

}// end class
?>
