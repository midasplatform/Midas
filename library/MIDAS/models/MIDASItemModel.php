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
  
  /** Add a revision to an item
   * @return void*/
  function addRevision($itemdao,$revisiondao)
    {
    if(!$itemdao instanceof ItemDao)
      {
      throw new Zend_Exception("First argument should be an item" );
      }
    if(!$revisiondao instanceof ItemRevisionDao)
      {
      throw new Zend_Exception("Second argument should be an item revision" );
      }

    $modelLoad = new MIDAS_ModelLoader();
    $ItemRevisionModel = $modelLoad->loadModel('ItemRevision');

    // Should check the latest revision for this item
    $latestrevision = $ItemRevisionModel->getLatestRevision($itemdao);
    if(!$latestrevision) // no revision yet we assigne the value 1
      {
      $revisiondao->setRevision(1);
      }
    else
      {
      $revisiondao->setRevision($latestrevision->getRevision()+1);
      }
    $revisiondao->setItemId($itemdao->getItemId());

    // TODO: Add the date but the database is doing it automatically so maybe not
    $ItemRevisionModel->save($revisiondao);
    } // end addRevision
  
} // end class MIDASItemModel
?>