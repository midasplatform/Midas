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
//App::import("Vendor",'Sanitize');
require_once BASE_PATH.'/modules/api/models/base/UserapiModelBase.php';

/** User api key model implementation */
class Api_UserapiModel extends Api_UserapiModelBase
{
  /**
   * Create an API key from a login and password.  The password passed to this
   * function should not be hashed, it should be the actual password.
   */
  function createKeyFromEmailPassword($appname, $email, $password)
    {
    if(!is_string($appname) || !is_string($email) || !is_string($password))
      {
      throw new Zend_Exception("Error parameter");
      }

    $this->ModelLoader = new MIDAS_ModelLoader();
    $userModel = $this->ModelLoader->loadModel('User');

    // First check that the email and password are correct (ldap not supported for now)
    $userDao = $userModel->getByEmail($email);
    $passwordPrefix = Zend_Registry::get('configGlobal')->password->prefix;
    if($userDao == false || md5($passwordPrefix.$password) != $userDao->getPassword())
      {
      return false;
      }

    // Find if we already have an apikey
    $ret = $this->getByAppAndEmail($appname, $email);

    if($ret instanceof Api_UserapiDao)
      {
      return $ret;
      }
    else
      {
      // Create the APIKey
      $tokenexperiationtime = '100';
      return $this->createKey($userDao, $appname, $tokenexperiationtime);
      }
    return false;
    } // end function createKeyFromEmailPassword

  /**
   * Get UserapiDao by
   * @param string $appname Application Name
   * @param string $email
   * @return Api_UserapiDao
   */
  function getByAppAndEmail($appname, $email)
    {
    if(!is_string($appname) || !is_string($email))
      {
      throw new Zend_Exception("Error parameter");
      }
    $this->ModelLoader = new MIDAS_ModelLoader();
    $userModel = $this->ModelLoader->loadModel('User');
    $userDao = $userModel->getByEmail($email);
    if($userDao == false)
      {
      return false;
      }
    $row = $this->database->fetchRow($this->database->select()->where('application_name = ?', $appname)
                                                              ->where('user_id = ?', $userDao->getKey()));
    $dao = $this->initDao('Userapi', $row, 'api');
    return $dao;
    } // end getByApikey

  /**
   * Get UserapiDao by
   * @param string $appname Application Name
   * @param UserDao $userDao
   * @return Api_UserapiDao
   */
  function getByAppAndUser($appname, $userDao)
    {
    if(!is_string($appname) || !$userDao instanceof UserDao)
      {
      throw new Zend_Exception("Error parameter");
      }
    $row = $this->database->fetchRow($this->database->select()->where('application_name = ?', $appname)
                                                              ->where('user_id = ?', $userDao->getKey()));
    $dao = $this->initDao('Userapi', $row, 'api');
    return $dao;
    } // end getByAppAndUser


  /**
   * Return the tokendao
   * @param type $email
   * @param type $apikey
   * @param type $appname
   * @return Api_TokenDao
   */
  function getToken($email, $apikey, $appname)
    {
    if(!is_string($appname) || !is_string($apikey) || !is_string($email))
      {
      throw new Zend_Exception("Error parameter");
      }
    // Check if we don't have already a token
    $this->ModelLoader = new MIDAS_ModelLoader();
    $userModel = $this->ModelLoader->loadModel('User');
    $userDao = $userModel->getByEmail($email);
    if(!$userDao)
      {
      return false;
      }
    $now = date("c");

    $sql = $this->database->select()
                      ->setIntegrityCheck(false)
                      ->from(array('t' => 'api_token'))
                      ->join(array('u' => 'api_userapi'),
                         ' u.userapi_id= t.userapi_id', array() )
                      ->where('u.user_id = ?', $userDao->getKey())
                      ->where('u.application_name = ?', $appname)
                      ->where('t.expiration_date > ?', $now)
                      ->where('u.apikey = ?', $apikey);


    $row = $this->database->fetchRow($sql);
    $tokenDao = $this->initDao('Token', $row, 'api');

    if(!empty($tokenDao))
      {
      return $tokenDao;
      }

    // We generate a token
    $keychars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $length = 40;

    // seed with microseconds
    list($usec, $sec) = explode(' ', microtime());
    srand((float) $sec + ((float) $usec * 100000));

    $token = "";
    $max = strlen($keychars) - 1;
    for($i = 0; $i < $length; $i++)
      {
      $token .= substr($keychars, rand(0, $max), 1);
      }

    // Find the api id

    $sql =  $this->database->select()
                  ->setIntegrityCheck(false)
                  ->from(array('u' => 'api_userapi'))
                  ->where('u.user_id = ?', $userDao->getKey())
                  ->where('u.application_name = ?', $appname)
                  ->where('u.apikey = ?', $apikey);

    $row = $this->database->fetchRow($sql);
    $userapiDao = $this->initDao('Userapi', $row, 'api');

    if(!$userapiDao)
      {
      return false;
      }

    $this->loadDaoClass('TokenDao', 'api');
    $tokenDao = new Api_TokenDao();
    $tokenDao->setUserapiId($userapiDao->getKey());
    $tokenDao->setToken($token);
    $tokenDao->setExpirationDate(date("c", time() + $userapiDao->getTokenExpirationTime() * 60));

    $tokenModel = $this->ModelLoader->loadModel('Token', 'api');

    $tokenModel->save($tokenDao);

    // We do some cleanup of all the other keys that have expired
    $tokenModel->cleanExpired();

    return $tokenDao;
    } //get Token


  /** Return the userid from a token */
  function getUserapiFromToken($token)
    {
    if(!is_string($token))
      {
      throw new Zend_Exception("Error parameter");
      }
    $now = date("c");

    $sql = $this->database->select()
                  ->setIntegrityCheck(false)
                  ->from(array('u' => 'api_userapi'))
                  ->join(array('t' => 'api_token'),
                     ' u.userapi_id = t.userapi_id', array() )
                  ->where('t.expiration_date > ?', $now)
                  ->where('t.token = ?', $token);

    $row = $this->database->fetchRow($sql);
    return $this->initDao('Userapi', $row, 'api');
    }

  /** Get the user's keys */
  function getByUser($userDao)
    {
    if(!$userDao instanceof UserDao)
      {
      throw new Zend_Exception("Error parameter");
      }
    $rowset = $this->database->fetchAll($this->database->select()->where('user_id = ?', $userDao->getKey()));
    $return = array();
    foreach($rowset as $row)
      {
      $return[] = $this->initDao('Userapi', $row, 'api');
      }
    return $return;
    }
}
?>
