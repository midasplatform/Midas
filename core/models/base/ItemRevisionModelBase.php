<?php
class ItemRevisionModelBase extends AppModel
{
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'itemrevision';
    $this->_daoName = 'ItemRevisionDao';
    $this->_key = 'itemrevision_id';
    
    $this->_components = array('Filter');
  
    $this->_mainData= array(
      'itemrevision_id'=>  array('type'=>MIDAS_DATA),
      'item_id'=>  array('type'=>MIDAS_DATA),
      'revision' =>  array('type'=>MIDAS_DATA),
      'date' =>  array('type'=>MIDAS_DATA),
      'changes' =>  array('type'=>MIDAS_DATA),
      'user_id' => array('type'=>MIDAS_DATA),
      'bitstreams' =>  array('type'=>MIDAS_ONE_TO_MANY, 'model'=>'Bitstream', 'parent_column'=> 'itemrevision_id', 'child_column' => 'itemrevision_id'),
      'item' =>  array('type'=>MIDAS_MANY_TO_ONE, 'model'=>'Item', 'parent_column'=> 'item_id', 'child_column' => 'item_id'),
      'user' =>  array('type'=>MIDAS_MANY_TO_ONE, 'model'=>'User', 'parent_column'=> 'user_id', 'child_column' => 'user_id'),
      );
    $this->initialize(); // required
    } // end __construct()
  
  
  
} // end class ItemRevisionModelBase
?>