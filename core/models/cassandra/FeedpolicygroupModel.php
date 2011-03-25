<?php
require_once BASE_PATH.'/core/models/base/FeedpolicygroupModelBase.php';

/**
 * \class Feedpolicygroup
 * \brief Cassandra Model
 */
class FeedpolicygroupModel extends FeedpolicygroupModelBase
{
  /** getPolicy
   * @return FeedpolicygroupDao
   */
  public function getPolicy($group, $feed)
    {
    if(!$group instanceof GroupDao)
      {
      throw new Zend_Exception("Should be a group.");
      }
    if(!$feed instanceof FeedDao)
      {
      throw new Zend_Exception("Should be a feed.");
      }
      
    $feedid = $feed->getKey();
    $groupid = $group->getKey();
    
    $column = 'group_'.$groupid;    
    $feedarray = $this->database->getCassandra('feed',$feedid,array($column));
          
    // Massage the data to the proper format
    $newarray['feed_id'] = $feedid;
    $newarray['group_id'] = $groupid;
    $newarray['policy'] = $feedarray[$column];
    
    return $this->initDao('Feedpolicygroup',$newarray);  
    } // end getPolicy

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
      $feedid = $dao->getFeedId();
      $groupid = $dao->getGroupId();
      $column = 'group_'.$groupid;    

      $dataarray = array();
      $dataarray[$column] = $dao->getPolicy();
      
      $column_family = new ColumnFamily($this->database->getDB(),'feed');
      $column_family->insert($feedid,$dataarray);  
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
      // Remove the column group from the feed 
      $feedid = $dao->getFeedId();
      $groupid = $dao->getGroupId();
      $column = 'group_'.$groupid;   
      $cf = new ColumnFamily($this->database->getDB(),'feed');
      $cf->remove($feedid,array($column));      
      }    
    catch(Exception $e) 
      {
      throw new Zend_Exception($e); 
      }    
    $dao->saved=false;
    return true;
    }
    
}  // end class
?>
