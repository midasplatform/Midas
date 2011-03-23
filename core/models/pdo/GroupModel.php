<?php

/**
 *  UserModel
 *  Pdo Model
 */
class GroupModel  extends MIDASGroupModel
{
  /** create a group
   * @return GroupDao*/
  public function createGroup($communityDao,$name)
    {
    if(!$communityDao instanceof CommunityDao)
      {
      throw new Zend_Exception("Should be a acommunity.");
      }
    if(!is_string($name))
      {
      throw new Zend_Exception("Should be a string.");
      }
    $this->loadDaoClass('GroupDao');
    $group=new GroupDao();
    $group->setName($name);
    $group->setCommunityId($communityDao->getCommunityId());
    $this->save($group);
    return $group;
    }

  /** Add an user to a community
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
    $this->link('users',$group,$user);
    } // end function addItem

}// end class
?>