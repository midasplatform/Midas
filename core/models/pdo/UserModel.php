<?php
require_once BASE_PATH.'/core/models/base/UserModelBase.php';

/**
 *  UserModel
 *  Pdo Model
 */
class UserModel extends UserModelBase
{
  
  /** Get a user by email */
  function getByEmail($email)
    {
    $row = $this->database->fetchRow($this->database->select()->where('email = ?', $email)); 
    $dao= $this->initDao(ucfirst($this->_name),$row);
    return $dao;
    } // end getByEmail()
    
  /** Get a user by email */
  function getByUser_id($userid)
    {
    $row = $this->database->fetchRow($this->database->select()->where('user_id = ?', $userid)); 
    $dao= $this->initDao(ucfirst($this->_name),$row);
    return $dao;
    } // end getByUser_id() 
    
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
  function getUsersFromSearch($search,$userDao,$limit=14,$group=true,$order='view')
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


    $sql=$this->database->select();
    if($group)
      {
      $sql->from(array('u' => 'user'),array('user_id','firstname','lastname','count(*)'));
      }
    else
      {
      $sql->from(array('u' => 'user'));
      }
      
    $sql  ->where('(privacy='.MIDAS_USER_PUBLIC.' OR ('.
          $subqueryUser.')>0'.') AND ('.
          $this->database->getDB()->quoteInto('firstname LIKE ?','%'.$search.'%').' OR '.
          $this->database->getDB()->quoteInto('lastname LIKE ?','%'.$search.'%').')')          
          ->limit($limit)
          ->setIntegrityCheck(false);

    if($group)
      {
      $sql->group(array('firstname','lastname'));
      }      
          
    switch ($order)
      {
      case 'name':
        $sql->order(array('lastname ASC','firstname ASC'));
        break;
      case 'date':
        $sql->order(array('creation ASC'));
        break;
      case 'view': 
      default:
        $sql->order(array('view DESC'));
        break;
      }
    $rowset = $this->database->fetchAll($sql);
    $return = array();
    foreach($rowset as $row)
      {
      $tmpDao=$this->initDao('User', $row);
      if(isset($row['count(*)']))
        {
        $tmpDao->count = $row['count(*)'];
        }
      $return[] = $tmpDao;
      unset($tmpDao);
      }
    return $return;
    } // end getUsersFromSearch()
 
}// end class
?>