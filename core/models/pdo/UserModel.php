<?php
/*=========================================================================
 MIDAS Server
 Copyright (c) Kitware SAS. 26 rue Louis Guérin. 69100 Villeurbanne, FRANCE
 All rights reserved.
 More information http://www.kitware.com

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

         http://www.apache.org/licenses/LICENSE-2.0.txt

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
=========================================================================*/

require_once BASE_PATH.'/core/models/base/UserModelBase.php';

/**
 *  UserModel
 *  Pdo Model
 */
class UserModel extends UserModelBase
{
  /** get by uuid*/
  function getByUuid($uuid)
    {
    $row = $this->database->fetchRow($this->database->select()->where('uuid = ?', $uuid));
    $dao = $this->initDao(ucfirst($this->_name), $row);
    return $dao;
    }

  /** Get a user by email */
  function getByEmail($email)
    {
    $row = $this->database->fetchRow($this->database->select()->where('email = ?', $email));
    $dao = $this->initDao(ucfirst($this->_name), $row);
    return $dao;
    } // end getByEmail()

  /** Get a user by email */
  function getByUser_id($userid)
    {
    $row = $this->database->fetchRow($this->database->select()->where('user_id = ?', $userid));
    $dao = $this->initDao(ucfirst($this->_name), $row);
    return $dao;
    } // end getByUser_id()

  /** Get user communities */
  public function getUserCommunities($userDao)
    {
    if($userDao == null)
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
                                       ->from(array('u2g' => 'user2group'), array('group_id'))
                                       ->where('u2g.user_id = ?', $userDao->getUserId())
                                       .')' )
                 );
    $rowset = $this->database->fetchAll($sql);
    $return = array();
    foreach($rowset as $row)
      {
      $tmpDao = $this->initDao('Community', $row);
      $return[] = $tmpDao;
      unset($tmpDao);
      }
    return $return;
    } // end getUserCommunities

  /** Get all */
  function getAll($onlyPublic = false, $limit = 20, $order = 'lastname', $offset = null, $currentUser = null)
    {
    $sql = $this->database->select();
    if($onlyPublic)
      {
      $orClause = '';
      if($currentUser !== null && $currentUser->getPrivacy() == MIDAS_USER_PRIVATE)
        {
        $orClause = ' OR '.$this->database->getDB()->quoteInto('user_id = ? ', $currentUser->getUserId());
        }
      $sql->where('privacy = ?'.$orClause, MIDAS_USER_PUBLIC);
      }

    if($offset == null)
      {
      $sql->limit($limit);
      }
    elseif(!is_numeric($offset))
      {
      $sql->where('lastname LIKE ?', $offset.'%');
      $sql->limit($limit);
      }
    else
      {
      $sql->limit($limit, $offset);
      }
    switch($order)
      {
      case 'lastname':
        $sql->order(array('lastname ASC'));
        break;
      case 'view':
        $sql->order(array('view DESC', 'lastname ASC'));
        break;
      case 'admin':
        $sql->order(array('admin DESC'));
        break;
      default:
        $sql->order(array('lastname DESC'));
        break;
      }
    $rowset = $this->database->fetchAll($sql);
    $return = array();
    foreach($rowset as $row)
      {
      $return[] = $this->initDao('User', $row);
      }
    return $return;
    } // end getAll()

  /** Get admins */
  function getAdmins()
    {
    $sql = $this->database->select();
    $sql ->where('admin = ?', 1);

    $rowset = $this->database->fetchAll($sql);
    $return = array();
    foreach($rowset as $row)
      {
      $return[] = $this->initDao('User', $row);
      }
    return $return;
    } // end getAll()



  /** Returns a user given its folder (either public,private or base folder) */
  function getByFolder($folder)
    {
    if(!$folder instanceof FolderDao)
      {
      throw new Zend_Exception("Should be a folder" );
      }

    $row = $this->database->fetchRow($this->database->select()->setIntegrityCheck(false)
                                          ->from('user')
                                          ->where('folder_id=?', $folder->getFolderId())
                                          ->orwhere('publicfolder_id=?', $folder->getFolderId())
                                          ->orwhere('privatefolder_id=?', $folder->getFolderId())
                                          );

    $user = $this->initDao('User', $row);
    return $user;
    }

  /** Return a list of users corresponding to the search */
  function getUsersFromSearch($search, $userDao, $limit = 14, $group = true, $order = 'view')
    {
    if(Zend_Registry::get('configDatabase')->database->adapter == 'PDO_PGSQL')
      {
      $group = false; //Postgresql don't like the sql request with group by
      }
    $isAdmin = false;
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
      if($userDao->isAdmin())
        {
        $isAdmin = true;
        }
      }

    // Check that the user belong to the same group
    $subqueryUser = $this->database->select()
                          ->setIntegrityCheck(false)
                          ->from(array('g1' => 'user2group'),
                                 array('count(*)'))
                          ->joinLeft(array('g2' => 'user2group'),
                                     'g1.group_id = g2.group_id', array())
                          ->where('g1.user_id = u.user_id')
                          ->where('g2.user_id= ? ', $userId);


    $sql = $this->database->select();
    if($group)
      {
      $sql->from(array('u' => 'user'), array('user_id', 'firstname', 'lastname', 'count(*)'));
      }
    else
      {
      $sql->from(array('u' => 'user'));
      }

    if($isAdmin)
      {
      $sql  ->where(' ('.
          $this->database->getDB()->quoteInto('u.firstname LIKE ?', '%'.$search.'%').' OR '.
          $this->database->getDB()->quoteInto('u.lastname LIKE ?', '%'.$search.'%').')')
          ->limit($limit)
          ->setIntegrityCheck(false);
      }
    else
      {
      $sql  ->where('(u.privacy = '.MIDAS_USER_PUBLIC.' OR ('.
          $subqueryUser.')>0'.') AND ('.
          $this->database->getDB()->quoteInto('u.firstname LIKE ?', '%'.$search.'%').' OR '.
          $this->database->getDB()->quoteInto('u.lastname LIKE ?', '%'.$search.'%').')')
          ->limit($limit)
          ->setIntegrityCheck(false);
      }


    if($group)
      {
      $sql->group(array('u.firstname', 'u.lastname'));
      }

    switch($order)
      {
      case 'name':
        $sql->order(array('u.lastname ASC', 'u.firstname ASC'));
        break;
      case 'date':
        $sql->order(array('u.creation ASC'));
        break;
      case 'view':
      default:
        $sql->order(array('u.view DESC'));
        break;
      }
    $rowset = $this->database->fetchAll($sql);
    $return = array();
    foreach($rowset as $row)
      {
      $tmpDao = $this->initDao('User', $row);
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