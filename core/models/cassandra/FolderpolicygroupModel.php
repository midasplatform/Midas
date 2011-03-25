<?php
require_once BASE_PATH.'/core/models/base/FolderpolicygroupModelBase.php';

/**
 * \class Folderpolicygroup
 * \brief Cassandra Model
 */
class FolderpolicygroupModel extends FolderpolicygroupModelBase
{
  /** getPolicy
   * @return FolderpolicygroupDao
   */
  public function getPolicy($group, $folder)
    {
    if(!$group instanceof GroupDao)
      {
      throw new Zend_Exception("Should be a group.");
      }
    if(!$folder instanceof FolderDao)
      {
      throw new Zend_Exception("Should be a folder.");
      }
    $folderid = $folder->getKey();
    $groupid = $group->getKey();
    
    $column = 'group_'.$groupid;    
    $folderarray = $this->database->getCassandra('folder',$folderid,array($column));
          
    // Massage the data to the proper format
    $newarray['folder_id'] = $folderid;
    $newarray['group_id'] = $groupid;
    $newarray['policy'] = $folderarray[$column];
    
    return $this->initDao('Folderpolicygroup',$newarray);
    } // end getPolicy()
    
  /** Custom save command */
  public function save($dao)
    {
    $instance=$this->_name."Dao";
    if(!$dao instanceof $instance)
      {
      throw new Zend_Exception("Should be an object ($instance).");
      }
      
    try 
      {
      $folderid = $dao->getFolderId();
      $groupid = $dao->getGroupId();
      $column = 'group_'.$groupid;    

      $dataarray = array();
      $dataarray[$column] = $dao->getPolicy();
      
      $column_family = new ColumnFamily($this->database->getDB(),'folder');
      $column_family->insert($folderid,$dataarray);  
      } 
    catch(Exception $e) 
      {
      throw new Zend_Exception($e); 
      } 
    
    $dao->saved = true;
    return true;
    } // end save()  
    
  /** Custome delete command */
  public function delete($dao)
    {
    $instance=ucfirst($this->_name)."Dao";
    if(get_class($dao) !=  $instance)
      {
      throw new Zend_Exception("Should be an object ($instance). It was: ".get_class($dao) );
      }
    if(!$dao->saved)
      {
      throw new Zend_Exception("The dao should be saved first ...");
      }
    
    try 
      {
      // Remove the column group from the folder 
      $folderid = $dao->getFolderId();
      $groupid = $dao->getGroupId();
      $column = 'group_'.$groupid;   
      $cf = new ColumnFamily($this->database->getDB(),'folder');
      $cf->remove($folderid,array($column));      
      }    
    catch(Exception $e) 
      {
      throw new Zend_Exception($e); 
      }    
    $dao->saved=false;
    return true;
    }    
    
} // end class
?>
