<?php
class FeedpolicygroupModelBase extends AppModel
{
  public function __construct()
    {
    parent::__construct();
    $this->_name='feedpolicygroup';

    $this->_mainData=array(
        'feed_id'=>array(
        'type'=>MIDAS_DATA
      ),'group_id'=>array(
        'type'=>MIDAS_DATA
      ),'policy'=>array(
        'type'=>MIDAS_DATA
      ),'feed'=>array(
        'type'=>MIDAS_MANY_TO_ONE,'model'=>'Feed','parent_column'=>'feed_id','child_column'=>'feed_id'
      ),'group'=>array(
        'type'=>MIDAS_MANY_TO_ONE,'model'=>'Group','parent_column'=>'group_id','child_column'=>'group_id'
      )
      );
    $this->initialize(); // required
    } // end __construct()
 
  
} // end class FeedpolicygroupModelBase
?>