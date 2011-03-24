<?php
/**
 * \class FolderModel
 * \brief Cassandra Model
 */
class FolderModel extends MIDASFolderModel
{ 
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
    
  /** Custom save function*/
  public function save($folder)
    {
    if(!$folder instanceof FolderDao)
      {
      throw new Zend_Exception("Should be a folder.");
      }
    if($folder->getParentId()<=0)
      {
      $rightParent=0;
      }
    else
      {
      $parentFolder=$folder->getParent();
      $rightParent=$parentFolder->getRightIndice();
      }
    $data = array();
    foreach($this->_mainData as $key => $var)
      {
      if(isset($folder->$key))
        {
        $data[$key] = $folder->$key;
        }
      if($key=='right_indice')
        {
        $folder->$key=$rightParent+1;
        $data[$key]=$rightParent+1;
        }
      if($key=='left_indice')
        {
        $data[$key]=$rightParent;
        }
      }

    if(isset($data['folder_id']))
      {
      $key = $data['folder_id'];
      unset($data['folder_id']);
      unset($data['left_indice']);
      unset($data['right_indice']);
      
      $db = Zend_Registry::get('dbAdapter');
      $column_family->insert($key,$data); 
      return $key;
      }
    else
      {
      /*$this->_db->update('folder', array('right_indice'=> new Zend_Db_Expr('2 + right_indice')),
                          array('right_indice >= ?'=>$rightParent));
      $this->_db->update('folder', array('left_indice'=> new Zend_Db_Expr('2 + left_indice')),
                          array('left_indice >= ?'=>$rightParent));
      $insertedid = $this->insert($data);
      */
      $db = Zend_Registry::get('dbAdapter');
      $column_family = new ColumnFamily($db, 'folder');
    
      $uuid = CassandraUtil::uuid1();
      $column_family->insert($uuid,$data); 

      $folder->folder_id = bin2hex($uuid);
      $folder->saved=true;
      return true;
      }
    } // end method save  
    
    
} // end class FolderModel
?>
