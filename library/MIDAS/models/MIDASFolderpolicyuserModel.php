<?php
class MIDASFolderpolicyuserModel extends MIDASModel
{
  public function __construct()
    {
    parent::__construct();
    $this->_name='folderpolicyuser';
    $this->_mainData=array(
          'folder_id'=>array(
          'type'=>MIDAS_DATA
        ),'user_id'=>array(
          'type'=>MIDAS_DATA
        ),'policy'=>array(
          'type'=>MIDAS_DATA
        ),'folder'=>array(
          'type'=>MIDAS_MANY_TO_ONE,'model'=>'Folder','parent_column'=>'folder_id','child_column'=>'folder_id'
        ),'user'=>array(
          'type'=>MIDAS_MANY_TO_ONE,'model'=>'User','parent_column'=>'user_id','child_column'=>'user_id'
        )
        );
    $this->initialize(); // required
    } // end __construct()
  
  
  
} // end class MIDASFolderpolicyuserModel
?>