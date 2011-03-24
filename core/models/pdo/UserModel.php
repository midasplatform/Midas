<?php

/**
 *  UserModel
 *  Pdo Model
 */
class UserModel extends MIDASUserModel
{
  
  /** Get a user by email */
  function getByEmail($email)
    {
    $row = $this->database->fetchRow($this->database->select()->where('email = ?', $email)); 
    $dao= $this->initDao(ucfirst($this->_name),$row);
    return $dao;
    } // end getByEmail()
    
  /** Get user communities */
  public function getUserCommunities($userDao)
    {
    if($userDao==null)
      {
      return array();
      }
    if(!$userDao instanceof UserDao)
      {
      throw new Zend_Exception("Should be an user.");
      }
    $sql = $this->database->select()
          ->setIntegrityCheck(false)
          ->from('community')
          ->where('membergroup_id IN (' .new Zend_Db_Expr(
                                  $this->database->select()
                                       ->setIntegrityCheck(false)
                                       ->from(array('u2g' => 'user2group'),
                                              array('group_id'))
                                       ->where('u2g.user_id = ?' , $userDao->getUserId())
                                       .')' )
                 );
    $rowset = $this->database->fetchAll($sql);
    $return = array();
    foreach ($rowset as $row)
      {
      $tmpDao= $this->initDao('Community', $row);
      $return[] = $tmpDao;
      unset($tmpDao);
      }
    return $return;
    } // end getUserCommunities

  /** Return a list of users corresponding to the search */
  function getUsersFromSearch($search,$userDao)
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

    // Check that the user belong to the same group
    $subqueryUser= $this->database->select()
                          ->setIntegrityCheck(false)
                          ->from(array('g1' => 'user2group'),
                                 array('count(*)'))
                          ->joinLeft(array('g2' => 'user2group'),
                                     'g1.group_id=g2.group_id',array())
                          ->where('g1.user_id=u.user_id')
                          ->where('g2.user_id= ? ',$userId);


    $sql = $this->database->select()->from(array('u'=>$this->_name),array('user_id','firstname','lastname','count(*)'))
                                          ->where('(privacy='.MIDAS_USER_PUBLIC.' OR ('.
                                          $subqueryUser.')>0'.') AND ('.
                                          $this->database->getDB()->quoteInto('firstname LIKE ?','%'.$search.'%').' OR '.
                                          $this->database->getDB()->quoteInto('lastname LIKE ?','%'.$search.'%').')')
                                          ->group(array('firstname','lastname'))
                                          ->limit(14)
                                          ->setIntegrityCheck(false);

    $rowset = $this->database->fetchAll($sql);
    $return = array();
    foreach($rowset as $row)
      {
      $tmpDao = new UserDao();
      $tmpDao->count = $row['count(*)'];
      $tmpDao->setFirstname($row->firstname);
      $tmpDao->setLastname($row->lastname);
      $tmpDao->setUserId($row->user_id);
      $return[] = $tmpDao;
      unset($tmpDao);
      }
    return $return;
    } // end getUsersFromSearch()
 
}// end class
?>