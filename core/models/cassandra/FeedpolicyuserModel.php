<?php
require_once BASE_PATH.'/core/models/base/FeedpolicyuserModelBase.php';

/**
 * \class Feedpolicyuser
 * \brief Cassandra Model
 */
class FeedpolicyuserModel extends FeedpolicyuserModelBase
{
  /** getPolicy
   * @return FeedpolicyuserDao
   */
  public function getPolicy($user, $feed)
    {
    if(!$user instanceof UserDao)
      {
      throw new Zend_Exception("Should be a user.");
      }
    if(!$feed instanceof FeedDao)
      {
      throw new Zend_Exception("Should be a feed.");
      }
      
    $feedid = $feed->getKey();
    $userid = $user->getKey();
    
    $column = 'user_'.$userid;    
    $feedarray = $this->database->getCassandra('feed',$feedid,array($column));
          
    // Massage the data to the proper format
    $newarray['feed_id'] = $feedid;
    $newarray['user_id'] = $userid;
    $newarray['policy'] = $feedarray[$column];
    
    return $this->initDao('Feedpolicyuser',$newarray);  
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
      $userid = $dao->getUserId();
      $column = 'user_'.$userid;    

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
      // Remove the column user from the feed 
      $feedid = $dao->getFeedId();
      $userid = $dao->getUserId();
      $column = 'user_'.$userid;   
      $cf = new ColumnFamily($this->database->getDB(),'feed');
      $cf->remove($feedid,array($column));      
      }    
    catch(Exception $e) 
      {
      throw new Zend_Exception($e); 
      }    
    $dao->saved=false;
    return true;
    } // end delete()  
    
} // end class
?>
