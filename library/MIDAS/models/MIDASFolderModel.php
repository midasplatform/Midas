<?php
class MIDASFolderModel extends MIDASModel
{
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'folder';
    $this->_key = 'folder_id';
  
    $this->_components = array('Sortdao');

    $this->_mainData= array(
      'folder_id'=> array('type'=>MIDAS_DATA),
      'left_indice'=> array('type'=>MIDAS_DATA),
      'right_indice'=> array('type'=>MIDAS_DATA),
      'parent_id'=> array('type'=>MIDAS_DATA),
      'name'=> array('type'=>MIDAS_DATA),
      'description' =>  array('type'=>MIDAS_DATA),
      'date' =>  array('type'=>MIDAS_DATA),
      'items' =>  array('type'=>MIDAS_MANY_TO_MANY, 'model'=>'Item', 'table' => 'item2folder', 'parent_column'=> 'folder_id', 'child_column' => 'item_id'),
      'folderpolicygroup' =>  array('type'=>MIDAS_ONE_TO_MANY, 'model' => 'Folderpolicygroup', 'parent_column'=> 'folder_id', 'child_column' => 'folder_id'),
      'folderpolicyuser' =>  array('type'=>MIDAS_ONE_TO_MANY, 'model' => 'Folderpolicyuser', 'parent_column'=> 'folder_id', 'child_column' => 'folder_id'),
      'folders' => array('type'=>MIDAS_ONE_TO_MANY, 'model'=>'Folder', 'parent_column'=> 'folder_id', 'child_column' => 'parent_id'),
      'parent' => array('type'=>MIDAS_MANY_TO_ONE, 'model'=>'Folder', 'parent_column'=> 'parent_id', 'child_column' => 'folder_id'),
      );
    $this->initialize(); // required
    } // end __construct() 
  
  
  
} // end class MIDASFolderModel
?>