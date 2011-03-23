<?php
class MIDASGroupModel extends MIDASModel
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

} // end class MIDASGroupModel
?>