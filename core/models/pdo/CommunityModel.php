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
    $dao= $this->initDao(ucfirst($this->_name),$row);
    return $dao;
    } // end getByName()
     
  /* get public Communities
   * 
   * @return Array of Community Dao
   */
  function getPublicCommunities($limit=20)
    {
    if(!is_numeric($limit))
      {
      throw new Zend_Exception("Error parameter.");
      }
    $sql = $this->database->select()->from($this->_name)
                          ->where('privacy != ?',MIDAS_COMMUNITY_PRIVATE)
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
  function getCommunitiesFromSearch($search,$userDao)
    {
    if($userDao==null)
      {
      $userId= -1;
      }
    else if(!$userDao instanceof UserDao)
      {
      throw new Zend_Exception("Should be an user.");
      }
    else
      {
      $userId = $userDao->getUserId();
      }
      
    $sql = $this->database->select()->from($this->_name,array('community_id','name','count(*)'))
                                          ->where('name LIKE ?','%'.$search.'%')
                                          ->where('privacy < '.MIDAS_COMMUNITY_PRIVATE)
                                          ->group('name')
                                          ->limit(14);
      
    $rowset = $this->database->fetchAll($sql);
    $return = array();
    foreach($rowset as $row)
      {
      $tmpDao = new CommunityDao();
      $tmpDao->count = $row['count(*)'];
      $tmpDao->setName($row->name);
      $tmpDao->setCommunityId($row->community_id);
      $return[] = $tmpDao;
      unset($tmpDao);
      }
    return $return;
    } // end getCommunitiesFromSearch()
  
}// end class
?>