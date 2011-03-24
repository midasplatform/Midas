<?php
class MIDASCommunityModel extends MIDASModel
{
  public function __construct()
    {
     parent::__construct();  
    $this->_name = 'community';
    $this->_key = 'community_id';
    $this->_mainData = array(
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
    $this->initialize(); // required  
    } // end __construct()
  
  
  
} // end class MIDASCommunityModel
?>