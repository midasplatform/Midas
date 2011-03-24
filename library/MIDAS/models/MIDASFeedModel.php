<?php
class MIDASFeedModel extends MIDASModel
{
  public function __construct()
    {
    parent::__construct();  
    $this->_name = 'feed';
    $this->_key = 'feed_id'; 
    $this->_components = array('Sortdao');
    $this->_mainData= array(
      'feed_id'=> array('type'=>MIDAS_DATA),
      'date'=> array('type'=>MIDAS_DATA),
      'user_id'=> array('type'=>MIDAS_DATA),
      'type'=> array('type'=>MIDAS_DATA),
      'ressource'=> array('type'=>MIDAS_DATA),
      'communities' =>  array('type'=>MIDAS_MANY_TO_MANY, 'model'=>'Community', 'table' => 'feed2community', 'parent_column'=> 'feed_id', 'child_column' => 'community_id'),
      'user' => array('type'=>MIDAS_MANY_TO_ONE, 'model'=>'User', 'parent_column'=> 'user_id', 'child_column' => 'user_id'),
      'feedpolicygroup' =>  array('type'=>MIDAS_ONE_TO_MANY, 'model' => 'Feedpolicygroup', 'parent_column'=> 'feed_id', 'child_column' => 'feed_id'),
      'feedpolicyuser' =>  array('type'=>MIDAS_ONE_TO_MANY, 'model' => 'Feedpolicyuser', 'parent_column'=> 'feed_id', 'child_column' => 'feed_id'),
      );
    $this->initialize(); // required
    } // end __construct() 
  
  
} // end class MIDASFeedModel
?>