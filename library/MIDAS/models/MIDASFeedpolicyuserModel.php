<?php
class MIDASFeedpolicyuserModel extends MIDASModel
{
  public function __construct()
    {
    parent::__construct();
    $this->_name='feedpolicyuser';
    $this->_mainData=array(
        'feed_id'=>array(
        'type'=>MIDAS_DATA
      ),'user_id'=>array(
        'type'=>MIDAS_DATA
      ),'policy'=>array(
        'type'=>MIDAS_DATA
      ),'feed'=>array(
        'type'=>MIDAS_MANY_TO_ONE,'model'=>'Feed','parent_column'=>'feed_id','child_column'=>'feed_id'
      ),'user'=>array(
        'type'=>MIDAS_MANY_TO_ONE,'model'=>'User','parent_column'=>'user_id','child_column'=>'user_id'
      )
      );
    $this->initialize(); // required
    } // end __construct()  
  
  
  
} // end class MIDASFeedpolicyuserModel
?>