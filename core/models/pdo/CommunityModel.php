<?php
require_once BASE_PATH.'/core/models/base/CommunityModelBase.php';

/**
 *  UserModel
 *  Pdo Model
 */
class CommunityModel extends CommunityModelBase
{ 

  /** Get a community by name */
  function getByName($name)
    {
    $row = $this->database->fetchRow($this->database->select()->where('name = ?', $name)); 
    $dao = $this->initDao(ucfirst($this->_name), $row);
    return $dao;
    } // end getByName()
    
    
  /** Get all */
  function getAll()
    {
    $rowset = $this->database->fetchAll($this->database->select()); 
    $return = array();
    foreach($rowset as $row)
      {      
      $return[] = $this->initDao('Community', $row);
      }
    return $return;
    } // end getByName()
     
  /** get public Communities
   * 
   * @return Array of Community Dao
   */
  function getPublicCommunities($limit = 20)
    {
    if(!is_numeric($limit))
      {
      throw new Zend_Exception("Error parameter.");
      }
    $sql = $this->database->select()->from($this->_name)
                          ->where('privacy != ?', MIDAS_COMMUNITY_PRIVATE)
                          ->limit($limit);
      
    $rowset = $this->database->fetchAll($sql);
    $return = array();
    foreach($rowset as $row)
      {      
      $return[] = $this->initDao('Community', $row);
      }
    return $return;
    }
  
  /** Return a list of communities corresponding to the search */
  function getCommunitiesFromSearch($search, $userDao, $limit = 14, $group = true, $order = 'view')
    {
    if(Zend_Registry::get('configDatabase')->database->adapter == 'PDO_PGSQL')
      {
      $group = false; //Postgresql don't like the sql request with group by
      }
    $communities = array();
    if($userDao == null)
      {
      $userId = -1;      
      }
    else if(!$userDao instanceof UserDao)
      {
      throw new Zend_Exception("Should be an user.");
      }
    else
      {
      $userId = $userDao->getUserId();      
      $userGroups = $userDao->getGroups();
      foreach($userGroups as $userGroup)
        {
        $communities[] = $userGroup->getCommunityId();
        }
      }
      
    $sql = $this->database->select();
    if($group)
      {
      $sql->from(array('c' => 'community'), array('community_id', 'name', 'count(*)'));
      }
    else
      {
      $sql->from(array('c' => 'community'));
      }        
          
    if($userId != -1 && $userDao->isAdmin())
      {
      $sql->where('c.name LIKE ?', '%'.$search.'%');
      }
    else if(!empty($communities))
      {
      $sql->where('c.name LIKE ?', '%'.$search.'%');
      $sql->where('(c.privacy < '.MIDAS_COMMUNITY_PRIVATE.' OR '.$this->database->getDB()->quoteInto('c.community_id IN (?)', $communities).')' );
      }   
    else
      {
      $sql->where('c.name LIKE ?', '%'.$search.'%');
      $sql->where('(c.privacy < '.MIDAS_COMMUNITY_PRIVATE.')');
      }
      
    $sql->limit($limit);
      
    if($group)
      {
      $sql->group('c.name');
      }
      
    switch($order)
      {
      case 'name':
        $sql->order(array('c.name ASC'));
        break;
      case 'date':
        $sql->order(array('c.creation ASC'));
        break;
      case 'view': 
      default:
        $sql->order(array('c.view DESC'));
        break;
      }
 
    $rowset = $this->database->fetchAll($sql);
    $return = array();
    foreach($rowset as $row)
      {
      $tmpDao = $this->initDao('Community', $row);
      if(isset($row['count(*)']))
        {
        $tmpDao->count = $row['count(*)'];
        }
      $return[] = $tmpDao;
      unset($tmpDao);
      }
    return $return;
    } // end getCommunitiesFromSearch()
  
}// end class