<?php

/**
 *  UserModel
 *  Pdo Model
 */
class GroupModel extends AppModelPdo
  {
  public $_name = 'group';
  public $_key = 'group_id';
  public $_mainData = array(
    'group_id' => array('type' => MIDAS_DATA),
    'community_id' => array('type' => MIDAS_DATA),
    'name' => array('type' => MIDAS_DATA),
    'community' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Community', 'parent_column' => 'community_id', 'child_column' => 'community_id'),
    'users' =>  array('type'=>MIDAS_MANY_TO_MANY, 'model'=>'User', 'table' => 'user2group', 'parent_column'=> 'group_id', 'child_column' => 'user_id'),
);


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