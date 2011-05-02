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
      'license' => array('type'=>MIDAS_DATA),
      'bitstreams' =>  array('type'=>MIDAS_ONE_TO_MANY, 'model'=>'Bitstream', 'parent_column'=> 'itemrevision_id', 'child_column' => 'itemrevision_id'),
      'item' =>  array('type'=>MIDAS_MANY_TO_ONE, 'model'=>'Item', 'parent_column'=> 'item_id', 'child_column' => 'item_id'),
      'user' =>  array('type'=>MIDAS_MANY_TO_ONE, 'model'=>'User', 'parent_column'=> 'user_id', 'child_column' => 'user_id'),
      );
    $this->initialize(); // required
    } // end __construct()
  
    /** delete a revision*/
  function delete($revisiondao)
    {
    if(!$revisiondao instanceof ItemRevisionDao)
      {
      throw new Zend_Exception("Error param.");
      }
    $bitstreams=$revisiondao->getBitstreams();
    $this->ModelLoader = new MIDAS_ModelLoader();
    $bitstream_model=$this->ModelLoader->loadModel('Bitstream');
    foreach($bitstreams as $bitstream)
      {
      $bitstream_model->delete($bitstream);
      }
    parent::delete($revisiondao);
    $revisiondao->saved=false;
    unset($revisiondao->itemrevision_id);
    }//end delete
    
  
} // end class ItemRevisionModelBase
?>