<?php
abstract class FolderModelBase extends AppModel
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

  /** Abstract functions */
  abstract function getChildrenFoldersFiltered($folder,$userDao=null,$policy=0);
  abstract function getItemsFiltered($folder,$userDao=null,$policy=0);
  abstract function getSizeFiltered($folders,$userDao=null,$policy=0);
  abstract function getCommunity($folder);
  abstract function getUser($folder);
  abstract function addItem($folder,$item);
  abstract function removeItem($folder,$item);
  
  /** Create a folder */
  function createFolder($name,$description,$parent)
    {
    if(!$parent instanceof FolderDao&&!is_numeric($parent))
      {
      throw new Zend_Exception("Should be a folder.");
      }
    if(!is_string($name)||!is_string($description))
      {
      throw new Zend_Exception("Should be a string.");
      }
    $this->loadDaoClass('FolderDao');
    $folder=new FolderDao();
    $folder->setName($name);
    $folder->setDescription($description);
    $folder->setDate(date('c'));
    if($parent instanceof FolderDao)
      {
      $parentId=$parent->getFolderId();
      }
    else
      {
      $parentId=$parent;
      }
    $folder->setParentId($parentId);
    $this->save($folder);
    return $folder;
    }
} // end class FolderModelBase
?>