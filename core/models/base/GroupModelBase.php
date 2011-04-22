<?php
abstract class GroupModelBase extends AppModel
{
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'group';
    $this->_key = 'group_id';
    $this->_mainData = array(
      'group_id' => array('type' => MIDAS_DATA),
      'community_id' => array('type' => MIDAS_DATA),
      'name' => array('type' => MIDAS_DATA),
      'community' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Community', 'parent_column' => 'community_id', 'child_column' => 'community_id'),
      'users' =>  array('type'=>MIDAS_MANY_TO_MANY, 'model'=>'User', 'table' => 'user2group', 'parent_column'=> 'group_id', 'child_column' => 'user_id'),
      );
    $this->initialize(); // required
    } // end __construct()  
  
  /** Add a user to a group */
  abstract function addUser($group,$user);
  abstract function removeUser($group,$user);
  abstract function findByCommunity($communityDao);
  abstract function getGroupFromSearch($search,$limit=14);
    
  /** Delete a group */
  public function deleteGroup($group)
    {
    if(!$group instanceof GroupDao)
      {
      throw new Zend_Exception("Should be a group.");
      }
    $users=$group->getUsers();
    foreach($users as $user)
      {
      $this->removeUser($group,$user);
      }
    parent::delete($group);
    unset($group->group_id);
    $group->saved=false;
    }//end deleteGroup
  
  
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

} // end class GroupModelBase
?>