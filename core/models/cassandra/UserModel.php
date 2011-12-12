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
 * \class UserModel
 * \brief Cassandra Model
 */
class UserModel extends UserModelBase
{
  /** Get a user by email */
  function getByEmail($email)
    {
    // We get from the table emailuser
    try
      {
      $emailuser = new ColumnFamily($this->database->getDB(), 'emailuser');
      $useridarray = $emailuser->get($email);
      $userid = $useridarray[$this->_key];
      $user = new ColumnFamily($this->database->getDB(), 'user');
      $userarray = $user->get($userid);

      // Add the user_id
      $userarray[$this->_key] = $userid;
      $dao = $this->initDao('User', $userarray);
      }
    catch(cassandra_NotFoundException $e)
      {
      return false;
      }
    catch(Exception $e)
      {
      throw new Zend_Exception($e);
      }

    return $dao;
    } // end getByEmail()

  /** Get a user by id */
  function getByUser_id($userid)
    {
    // We get from the table emailuser
    try
      {
      $user = new ColumnFamily($this->database->getDB(), 'user');
      $userarray = $user->get($userid);
      // Add the user_id
      $userarray[$this->_key] = $userid;
      $dao = $this->initDao('User', $userarray);
      }
    catch(cassandra_NotFoundException $e)
      {
      return false;
      }
    catch(Exception $e)
      {
      throw new Zend_Exception($e);
      }

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

    $return = array();
    try
      {
      $user = new ColumnFamily($this->database->getDB(), 'user');
      $communities = $user->get($userDao->getUserId(), array('communities'));

      echo "Cassandra:UserModel:GetUserCommuntiies";
      //var_dump($communities);
      exit();
      /*
      foreach($communities as $key => $values)
        {

        }
      */

      }
    catch(cassandra_NotFoundException $e)
      {
      return $return;
      }
    catch(Exception $e)
      {
      throw new Zend_Exception($e);
      }

    /*
    $rowset = $this->database->fetchAll($sql);
    $return = array();
    foreach($rowset as $row)
      {
      $tmpDao= $this->initDao('Community', $row);
      $return[] = $tmpDao;
      unset($tmpDao);
      }
    return $return;*/
    } // end getUserCommunities


  /** create user */
  public function createUser($email, $password, $firstname, $lastname, $admin = 0)
    {
    $userDao = parent::createUser($email, $password, $firstname, $lastname, $admin);
    // Add to the emailuser table
    $emailuser = new ColumnFamily($this->database->getDB(), 'emailuser');
    $emailuser->insert($userDao->getEmail(), array($this->_key => $userDao->user_id));

    // Add the userid to the folder
    $folder = new ColumnFamily($this->database->getDB(), 'folder');
    $folder->insert($userDao->getFolderId(), array('user_id' => $userDao->user_id));

    return $userDao;
    }
}

