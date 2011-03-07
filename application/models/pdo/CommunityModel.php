<?php

/**
 *  UserModel
 *  Pdo Model
 */
class CommunityModel extends AppModelPdo
{
  public $_name = 'community';
  public $_key = 'community_id';
  public $_mainData = array(
    'community_id' => array('type' => MIDAS_DATA),
    'name' => array('type' => MIDAS_DATA),
    'description' => array('type' => MIDAS_DATA),
    'creation' => array('type' => MIDAS_DATA),
    'privacy' => array('type' => MIDAS_DATA),
    'folder_id' => array('type' => MIDAS_DATA),
    'publicfolder_id' => array('type' => MIDAS_DATA),
    'privatefolder_id' => array('type' => MIDAS_DATA),
    'admingroup_id' => array('type' => MIDAS_DATA),
    'moderatorgroup_id' => array('type' => MIDAS_DATA),
    'membergroup_id' => array('type' => MIDAS_DATA),
    'folder' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Folder', 'parent_column' => 'folder_id', 'child_column' => 'folder_id'),
    'public_folder' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Folder', 'parent_column' => 'publicfolder_id', 'child_column' => 'folder_id'),
    'private_folder' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Folder', 'parent_column' => 'privatefolder_id', 'child_column' => 'folder_id'),
    'admin_group' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Group', 'parent_column' => 'admingroup_id', 'child_column' => 'group_id'),
    'moderator_group' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Group', 'parent_column' => 'moderatorgroup_id', 'child_column' => 'group_id'),
    'member_group' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Group', 'parent_column' => 'membergroup_id', 'child_column' => 'group_id'),
    'feeds' =>  array('type'=>MIDAS_MANY_TO_MANY, 'model'=>'Feed', 'table' => 'feed2community', 'parent_column'=> 'community_id', 'child_column' => 'feed_id'),
  );

  /* get public Communities
   * 
   * @return Array of Community Dao
   */
  function getPubicCommunities($limit=20)
    {
    if(!is_numeric($limit))
      {
      throw new Zend_Exception("Error parameter.");
      }
    $sql = $this->select()->from($this->_name)
                          ->where('privacy != ?',MIDAS_COMMUNITY_PRIVATE)
                          ->limit($limit);
      
    $rowset = $this->fetchAll($sql);
    $return = array();
    foreach($rowset as $row)
      {      
      $return[] = $this->initDao('Community', $row);
      }
    return $return;
    }
  
  /** Return a list of communities corresponding to the search */
  function getCommunitiesFromSearch($search,$userDao)
    {
    if($userDao==null)
      {
      $userId= -1;
      }
    else if(!$userDao instanceof UserDao)
      {
      throw new Zend_Exception("Should be an user.");
      }
    else
      {
      $userId = $userDao->getUserId();
      }
      
    $sql = $this->select()->from($this->_name,array('community_id','name','count(*)'))
                                          ->where('name LIKE ?','%'.$search.'%')
                                          ->where('privacy < '.MIDAS_COMMUNITY_PRIVATE)
                                          ->group('name')
                                          ->limit(14);
      
    $rowset = $this->fetchAll($sql);
    $return = array();
    foreach($rowset as $row)
      {
      $tmpDao = new CommunityDao();
      $tmpDao->count = $row['count(*)'];
      $tmpDao->setName($row->name);
      $tmpDao->setCommunityId($row->community_id);
      $return[] = $tmpDao;
      unset($tmpDao);
      }
    return $return;
    } // end getCommunitiesFromSearch()
  
}// end class
?>