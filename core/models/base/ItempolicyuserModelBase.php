<?php
class ItempolicyuserModelBase extends AppModel
{
  public function __construct()
    {
    parent::__construct();
    $this->_name='itempolicyuser';

    $this->_mainData=array(
        'item_id'=>array('type'=>MIDAS_DATA),
        'user_id'=>array('type'=>MIDAS_DATA),
        'policy'=>array('type'=>MIDAS_DATA),
        'item'=>array('type'=>MIDAS_MANY_TO_ONE,'model'=>'Item','parent_column'=>'item_id','child_column'=>'item_id'),
        'user'=>array('type'=>MIDAS_MANY_TO_ONE,'model'=>'User','parent_column'=>'user_id','child_column'=>'user_id')
      );
    $this->initialize(); // required
    } // end __construct()  
  
  
  
} // end class ItempolicyuserModelBase
?>