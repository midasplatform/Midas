<?php
class MIDASFolderpolicygroupModel extends MIDASModel
{
  public function __construct()
    {
    parent::__construct();
    $this->_name='folderpolicygroup';
    $this->_mainData=array(
          'folder_id'=>array(
          'type'=>MIDAS_DATA
        ),'group_id'=>array(
          'type'=>MIDAS_DATA
        ),'policy'=>array(
          'type'=>MIDAS_DATA
        ),'folder'=>array(
          'type'=>MIDAS_MANY_TO_ONE,'model'=>'Folder','parent_column'=>'folder_id','child_column'=>'folder_id'
        ),'group'=>array(
          'type'=>MIDAS_MANY_TO_ONE,'model'=>'Group','parent_column'=>'group_id','child_column'=>'group_id'
        )
        );
    $this->initialize(); // required
    } // end __construct()
  
  
} // end class MIDASFolderpolicygroupModel
?>