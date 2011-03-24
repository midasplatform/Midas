<?php
class MIDASItemModel extends MIDASModel
{
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'item';
    $this->_key = 'item_id';

    $this->_mainData= array(
      'item_id'=>  array('type'=>MIDAS_DATA),
      'name' =>  array('type'=>MIDAS_DATA),
      'description' =>  array('type'=>MIDAS_DATA),
      'type' =>  array('type'=>MIDAS_DATA),
      'sizebytes'=>array('type'=>MIDAS_DATA),
      'date'=>array('type'=>MIDAS_DATA),
      'thumbnail'=>array('type'=>MIDAS_DATA),
      'folders' =>  array('type'=>MIDAS_MANY_TO_MANY, 'model'=>'Folder', 'table' => 'item2folder', 'parent_column'=> 'item_id', 'child_column' => 'folder_id'),
      'revisions' =>  array('type'=>MIDAS_ONE_TO_MANY, 'model'=>'ItemRevision', 'parent_column'=> 'item_id', 'child_column' => 'item_id'),
      'keywords' => array('type'=>MIDAS_MANY_TO_MANY, 'model'=>'ItemKeyword', 'table' => 'item2keyword', 'parent_column'=> 'item_id', 'child_column' => 'keyword_id'),
      );
    $this->initialize(); // required
    } // end __construct()  
  
  
  
} // end class MIDASItemModel
?>