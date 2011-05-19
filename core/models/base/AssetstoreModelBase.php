<?php
/** Assetstore Model Base*/
abstract class AssetstoreModelBase extends AppModel
{
  /** Constructor*/
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'assetstore';
    $this->_key = 'assetstore_id';
  
    $this->_mainData = array(
        'assetstore_id' =>  array('type' => MIDAS_DATA),
        'name' =>  array('type' => MIDAS_DATA),
        'itemrevision_id' =>  array('type' => MIDAS_DATA),
        'path' =>  array('type' => MIDAS_DATA),
        'type' =>  array('type' => MIDAS_DATA),
        'bitstreams' =>  array('type' => MIDAS_ONE_TO_MANY, 'model' => 'Bitstream', 'parent_column' => 'assetstore_id', 'child_column' => 'assetstore_id'),
        );
    $this->initialize(); // required
    } // end __construct()
   
  /** Abstract functions */
  abstract function getAll();
  
  /** save an assetsore*/
  public function save($dao)
    {
    parent::save($dao);
    $modelLoad = new MIDAS_ModelLoader();
    $uuModel = $modelLoad->loadModel('Uniqueidentifier');
    $uuModel->newUUID($dao);
    }
  
  /** delete an assetstore (and all the items in it)*/
  public function delete($dao)
    {
    if(!$dao instanceof AssetstoreDao)
      {
      throw new Zend_Exception("Error param.");
      }
    $bitreams = $dao->getBitstreams();
    $items = array();
    foreach($bitreams as $key => $bitstream)
      {
      $revision = $bitstream->getItemrevision();
      if(empty($revision))
        {
        continue;
        }
      $item = $revision->getItem();

      if(empty($item))
        {
        continue;
        }
      
      $items[$item->getKey()] = $item;
      }
      
    $modelLoader = new MIDAS_ModelLoader();
    $item_model = $modelLoader->loadModel('Item');
    foreach($items as $item)
      {
      $item_model->delete($item);
      }
    $modelLoad = new MIDAS_ModelLoader();
    $uuModel = $modelLoad->loadModel('Uniqueidentifier');
    $uudao = $uuModel->getIndentifier($dao);
    if($uudao)
      {
      $uuModel->delete($uudao);
      }
    parent::delete($dao);
    }// delete
 
} // end class AssetstoreModelBase