<?php
require_once BASE_PATH.'/core/models/base/CommunityModelBase.php';

/**
 * \class FeedModel
 * \brief Cassandra Model
 */
class CommunityModel extends CommunityModelBase
{
  function getPublicCommunities($limit=20)
    {
    if(!is_numeric($limit))
      {
      throw new Zend_Exception("Error parameter.");
      }
    
    $communities = array();
      
    // We assume we don't have a lot of communities
    // We get from the table emailuser
    try 
      {
      $community = new ColumnFamily($this->database->getDB(), 'community'); 
      $allcommunities = $community->get_range("", // start
                                              "", // end
                                              $limit // row count
                                              );

      foreach($allcommunities as $key => $com)
        {
        if($com['privacy'] != MIDAS_COMMUNITY_PRIVATE)
          {
          $com[$this->_key] = $key;                                           
          $communities[] = $this->initDao('Community', $com);
          }
        }    
      }
    catch(cassandra_NotFoundException $e) 
      {
      return $communities;  
      }      
    catch(Exception $e) 
      {
      throw new Zend_Exception($e); 
      }
    return $communities;
    }
  
  /** Get a community by name */
  function getByName($name)
    {
    // We assume we don't have a lot of communities
    // Otherwise we'll do an index table
    try 
      {
      $community = new ColumnFamily($this->database->getDB(), 'community'); 
      $communitiesarray = $community->get_range();
      
      foreach($communitiesarray as $key => $value)
        {
        if($value['name'] == $name)
          {
          // Add the community_id
          $value[$this->_key] = $key;
          $dao= $this->initDao('Community',$value);      
          return $dao;
          }  
        }  
      }
    catch(cassandra_NotFoundException $e) 
      {
      return false;  
      }      
    catch(Exception $e) 
      {
      throw new Zend_Exception($e); 
      }  
    return false;
    } // end getByName()
  
} // end class CommunityModel
?>
